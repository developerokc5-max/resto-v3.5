<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlertService
{
    // Platform display config
    private const PLATFORMS = ['grab', 'foodpanda'];

    private const PLATFORM_LABELS = [
        'grab'      => 'Grab',
        'foodpanda' => 'FoodPanda',
    ];

    /**
     * Check all stores for status changes and send alerts if needed.
     * Called after every platform scrape.
     */
    public function checkAndAlert(): void
    {
        // Get current platform status grouped by shop
        $currentStatuses = DB::table('platform_status')
            ->get()
            ->groupBy('shop_id');

        // Pre-load ALL open alerts in ONE query instead of N queries in the loop
        $openAlerts = DB::table('alert_logs')
            ->where('type', 'offline')
            ->whereNull('recovered_at')
            ->orderByDesc('alerted_at')
            ->get()
            ->groupBy('shop_id')
            ->map(fn($group) => $group->first());

        // Only send notifications during operating hours (6AM–12AM SGT)
        $sgtHour = (int) Carbon::now('Asia/Singapore')->format('G');
        $notifyEnabled = $sgtHour >= 6;

        // Resolve recipients once for the whole run
        $recipients  = $this->getRecipients();
        $waNumber    = DB::table('configurations')->where('key', 'whatsapp_number')->value('value');
        $waApikey    = DB::table('configurations')->where('key', 'whatsapp_apikey')->value('value');
        $appUrl      = rtrim(env('APP_URL', 'https://resto-v3-5.onrender.com'), '/');

        // Pre-load maintenance mode shops — skip alerting for these
        try {
            $maintenanceShops = DB::table('shops')
                ->where('maintenance_mode', true)
                ->pluck('shop_id')
                ->flip();
        } catch (\Exception $e) {
            $maintenanceShops = collect();
        }

        foreach ($currentStatuses as $shopId => $platforms) {
            // Skip shops in maintenance mode
            if ($maintenanceShops->has((string) $shopId)) continue;
            $shopName     = $platforms->first()->shop_name
                            ?? DB::table('shops')->where('shop_id', $shopId)->value('shop_name')
                            ?? (string) $shopId;
            $totalCount   = $platforms->count();
            $offlineCount = $platforms->where('is_online', false)->count();
            $allOffline   = $offlineCount === $totalCount && $totalCount > 0;

            $openAlert = $openAlerts->get($shopId);

            // Build per-platform status map for the email
            $platformStatuses = [];
            foreach ($platforms as $p) {
                $platformStatuses[strtolower($p->platform)] = (bool) $p->is_online;
            }

            if ($allOffline && !$openAlert) {
                // Store just went fully offline — create alert + send email
                $offlinePlatforms = $platforms->where('is_online', false)
                    ->pluck('platform')->toArray();

                $alertId = DB::table('alert_logs')->insertGetId([
                    'shop_id'            => $shopId,
                    'shop_name'          => $shopName,
                    'type'               => 'offline',
                    'platforms_affected' => json_encode($offlinePlatforms),
                    'alerted_at'         => Carbon::now(),
                    'email_sent'         => false,
                    'created_at'         => Carbon::now(),
                    'updated_at'         => Carbon::now(),
                ]);

                $sent = $notifyEnabled && $this->sendOfflineEmail($shopId, $shopName, $platformStatuses, $recipients);
                if ($notifyEnabled) {
                    $this->sendWhatsApp(
                        "🔴 {$shopName} is OFFLINE on all platforms!\nCheck: {$appUrl}/store/{$shopId}",
                        $waNumber, $waApikey
                    );
                }

                DB::table('alert_logs')->where('id', $alertId)
                    ->update(['email_sent' => $sent]);

            } elseif (!$allOffline && $openAlert) {
                // Store recovered — close alert + send recovery email
                $downtimeMinutes = (int) Carbon::parse($openAlert->alerted_at)
                    ->diffInMinutes(Carbon::now());

                DB::table('alert_logs')->where('id', $openAlert->id)->update([
                    'recovered_at'     => Carbon::now(),
                    'downtime_minutes' => $downtimeMinutes,
                    'updated_at'       => Carbon::now(),
                ]);

                $duration = $this->formatDuration($downtimeMinutes);
                if ($notifyEnabled) {
                    $this->sendRecoveryEmail($shopId, $shopName, $platformStatuses, $downtimeMinutes, $recipients);
                    $this->sendWhatsApp(
                        "✅ {$shopName} is back ONLINE. Was down {$duration}.",
                        $waNumber, $waApikey
                    );
                }
            }
        }
    }

    // ── Recipients ────────────────────────────────────────────────────────────

    private function getRecipients(): array
    {
        $configEmail = DB::table('configurations')->where('key', 'alert_email')->value('value');
        if ($configEmail) {
            return array_values(array_filter(array_map('trim', explode(',', $configEmail))));
        }
        $envEmails = env('ALERT_TO_EMAILS');
        if ($envEmails) {
            return array_values(array_filter(array_map('trim', explode(',', $envEmails))));
        }
        Log::error('AlertService: No alert recipients configured. Set ALERT_TO_EMAILS env var or configure alert_email in Settings.');
        return [];
    }

    // ── Offline Email ─────────────────────────────────────────────────────────

    private function sendOfflineEmail(string $shopId, string $shopName, array $platformStatuses, array $to): bool
    {
        $apiKey = env('RESEND_API_KEY');
        $from   = env('ALERT_FROM_EMAIL', 'onboarding@resend.dev');

        if (!$apiKey) {
            Log::warning('AlertService: RESEND_API_KEY not set');
            return false;
        }

        $time    = Carbon::now('Asia/Singapore')->format('j M Y, g:i A');
        $appUrl  = rtrim(env('APP_URL', 'https://resto-v3-5.onrender.com'), '/');
        $storeUrl = "{$appUrl}/store/{$shopId}";

        $subject = "🔴 {$shopName} — All Platforms Offline · {$time} SGT";

        $platformRows = $this->buildPlatformRows($platformStatuses);

        $html = $this->offlineEmailHtml($shopName, $time, $platformRows, $storeUrl, $appUrl);

        return $this->sendViaResend($apiKey, $from, $to, $subject, $html, "sendOfflineEmail:{$shopName}");
    }

    // ── Daily Summary Email ───────────────────────────────────────────────────

    public function sendDailySummary(): void
    {
        $apiKey     = env('RESEND_API_KEY');
        $from       = env('ALERT_FROM_EMAIL', 'onboarding@resend.dev');
        $recipients = $this->getRecipients();
        $appUrl     = rtrim(env('APP_URL', 'https://resto-v3-5.onrender.com'), '/');

        if (!$apiKey || empty($recipients)) return;

        $now     = Carbon::now('Asia/Singapore');
        $since   = $now->copy()->subDay();
        $dateStr = $now->format('j M Y');

        // Incidents in last 24h
        $incidents = DB::table('alert_logs')
            ->where('alerted_at', '>=', $since)
            ->orderByDesc('alerted_at')
            ->get();

        $totalIncidents  = $incidents->count();
        $resolved        = $incidents->whereNotNull('recovered_at')->count();
        $stillOpen       = $incidents->whereNull('recovered_at')->count();
        $openDowntime    = $incidents->whereNull('recovered_at')->sum(fn($i) => Carbon::parse($i->alerted_at)->diffInMinutes(Carbon::now()));
        $totalDowntime   = (int) ($incidents->whereNotNull('recovered_at')->sum('downtime_minutes') + $openDowntime);

        // Current platform status
        $platformStats = DB::table('platform_status')
            ->selectRaw('platform, SUM(CASE WHEN is_online THEN 1 ELSE 0 END) as online, COUNT(*) as total')
            ->whereIn('platform', ['grab', 'foodpanda'])
            ->groupBy('platform')
            ->get()->keyBy('platform');

        $incidentRows = '';
        foreach ($incidents->take(10) as $inc) {
            $dur = $inc->downtime_minutes ? $this->formatDuration((int)$inc->downtime_minutes) : ($inc->recovered_at ? '?' : 'ongoing');
            $status = $inc->recovered_at ? '✅ Resolved' : '🔴 Open';
            $incidentRows .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #f1f5f9;'>{$inc->shop_name}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #f1f5f9;'>{$status}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #f1f5f9;text-align:right;'>{$dur}</td>
            </tr>";
        }

        $platformRows = '';
        foreach (['grab' => 'Grab', 'foodpanda' => 'FoodPanda'] as $key => $label) {
            $p = $platformStats[$key] ?? null;
            $pct = $p && $p->total > 0 ? round($p->online / $p->total * 100) : 100;
            $color = $pct >= 90 ? '#16a34a' : ($pct >= 70 ? '#d97706' : '#dc2626');
            $platformRows .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #f1f5f9;'><strong>{$label}</strong></td>
                <td style='padding:8px 12px;border-bottom:1px solid #f1f5f9;text-align:right;color:{$color};font-weight:700;'>{$pct}% online</td>
            </tr>";
        }

        $html = "<!DOCTYPE html><html><body style='margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f1f5f9;padding:32px 16px;'>
  <tr><td align='center'>
    <table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;width:100%;'>
      <tr><td style='background:#0f172a;border-radius:12px 12px 0 0;padding:28px 32px;'>
        <p style='margin:0;color:#94a3b8;font-size:13px;font-weight:600;letter-spacing:1px;text-transform:uppercase;'>HawkerOps Daily Summary</p>
        <h1 style='margin:8px 0 0;color:#ffffff;font-size:22px;font-weight:700;'>📊 Daily Report · {$dateStr}</h1>
      </td></tr>
      <tr><td style='background:#ffffff;padding:32px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;'>

        <div style='display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:28px;'>
          <div style='background:#f8fafc;border-radius:8px;padding:16px;text-align:center;'>
            <div style='font-size:28px;font-weight:700;color:#0f172a;'>{$totalIncidents}</div>
            <div style='font-size:12px;color:#64748b;margin-top:4px;'>Incidents</div>
          </div>
          <div style='background:#f8fafc;border-radius:8px;padding:16px;text-align:center;'>
            <div style='font-size:28px;font-weight:700;color:" . ($stillOpen > 0 ? '#dc2626' : '#16a34a') . ";'>{$stillOpen}</div>
            <div style='font-size:12px;color:#64748b;margin-top:4px;'>Still Open</div>
          </div>
          <div style='background:#f8fafc;border-radius:8px;padding:16px;text-align:center;'>
            <div style='font-size:28px;font-weight:700;color:#0f172a;'>" . ($totalDowntime >= 60 ? floor($totalDowntime/60).'h '.($totalDowntime%60).'m' : $totalDowntime.'m') . "</div>
            <div style='font-size:12px;color:#64748b;margin-top:4px;'>Total Downtime</div>
          </div>
        </div>

        <h3 style='margin:0 0 12px;font-size:14px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;'>Platform Status</h3>
        <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e2e8f0;border-radius:8px;border-collapse:separate;border-spacing:0;overflow:hidden;margin-bottom:24px;'>
          {$platformRows}
        </table>

        " . ($totalIncidents > 0 ? "
        <h3 style='margin:0 0 12px;font-size:14px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;'>Incidents (last 24h)</h3>
        <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e2e8f0;border-radius:8px;border-collapse:separate;border-spacing:0;overflow:hidden;margin-bottom:24px;'>
          <tr style='background:#f8fafc;'><th style='padding:8px 12px;text-align:left;font-size:11px;color:#64748b;'>Store</th><th style='padding:8px 12px;text-align:left;font-size:11px;color:#64748b;'>Status</th><th style='padding:8px 12px;text-align:right;font-size:11px;color:#64748b;'>Downtime</th></tr>
          {$incidentRows}
        </table>" : "<p style='color:#16a34a;font-weight:600;margin:0 0 24px;'>✅ No incidents in the last 24 hours.</p>") . "

        <a href='{$appUrl}/dashboard' style='display:inline-block;background:#0f172a;color:#ffffff;padding:12px 22px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:700;'>View Dashboard →</a>
      </td></tr>
      <tr><td style='background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:16px 32px;text-align:center;'>
        <p style='margin:0;color:#94a3b8;font-size:12px;'>HawkerOps · Daily automated report</p>
      </td></tr>
    </table>
  </td></tr>
</table></body></html>";

        $subject = $totalIncidents > 0
            ? "📊 HawkerOps Daily · {$totalIncidents} incident(s), {$stillOpen} open · {$dateStr}"
            : "📊 HawkerOps Daily · All clear ✅ · {$dateStr}";

        $this->sendViaResend($apiKey, $from, $recipients, $subject, $html, 'sendDailySummary');

        // WhatsApp summary (short version)
        $waNumber = DB::table('configurations')->where('key', 'whatsapp_number')->value('value');
        $waApikey = DB::table('configurations')->where('key', 'whatsapp_apikey')->value('value');
        $waMsg = $totalIncidents > 0
            ? "📊 HawkerOps Daily ({$dateStr}): {$totalIncidents} incident(s), {$stillOpen} still open. Check: {$appUrl}/alerts"
            : "📊 HawkerOps Daily ({$dateStr}): All clear! No incidents. ✅";
        $this->sendWhatsApp($waMsg, $waNumber, $waApikey);
    }

    // ── Recovery Email ────────────────────────────────────────────────────────

    private function sendRecoveryEmail(string $shopId, string $shopName, array $platformStatuses, int $downtimeMinutes, array $to): bool
    {
        $apiKey = env('RESEND_API_KEY');
        $from   = env('ALERT_FROM_EMAIL', 'onboarding@resend.dev');

        if (!$apiKey) return false;

        $time     = Carbon::now('Asia/Singapore')->format('j M Y, g:i A');
        $duration = $this->formatDuration($downtimeMinutes);
        $appUrl   = rtrim(env('APP_URL', 'https://resto-v3-5.onrender.com'), '/');
        $storeUrl = "{$appUrl}/store/{$shopId}";

        $subject = "✅ {$shopName} — Back Online · Was down {$duration}";

        $platformRows = $this->buildPlatformRows($platformStatuses);

        $html = $this->recoveryEmailHtml($shopName, $time, $duration, $platformRows, $storeUrl, $appUrl);

        return $this->sendViaResend($apiKey, $from, $to, $subject, $html, "sendRecoveryEmail:{$shopName}");
    }

    // ── WhatsApp (CallMeBot) ──────────────────────────────────────────────────

    private function sendWhatsApp(string $message, ?string $number, ?string $apikey): void
    {
        if (!$number || !$apikey) return;

        $url = 'https://api.callmebot.com/whatsapp.php?' . http_build_query([
            'phone'  => $number,
            'text'   => $message,
            'apikey' => $apikey,
        ]);

        try {
            Http::timeout(10)->get($url);
            Log::info('AlertService: WhatsApp sent');
        } catch (\Exception $e) {
            Log::error('AlertService: WhatsApp failed', ['error' => $e->getMessage()]);
        }
    }

    // ── Scraper Failure Alert ─────────────────────────────────────────────────

    public function checkScraperFailure(): void
    {
        $hours = (int) (DB::table('configurations')->where('key', 'scraper_alert_hours')->value('value') ?? 2);
        if ($hours <= 0) return;

        $lastScrapeAt = DB::table('platform_status')->max('last_checked_at');
        if (!$lastScrapeAt) return;

        $hoursSince = Carbon::parse($lastScrapeAt)->diffInMinutes(Carbon::now()) / 60;
        if ($hoursSince < $hours) return;

        $hoursSinceFormatted = round($hoursSince, 1);
        $time = Carbon::now('Asia/Singapore')->format('j M Y, g:i A');
        $appUrl = rtrim(env('APP_URL', 'https://resto-v3-5.onrender.com'), '/');

        $recipients = $this->getRecipients();
        $apiKey     = env('RESEND_API_KEY');
        $from       = env('ALERT_FROM_EMAIL', 'onboarding@resend.dev');

        if ($apiKey && !empty($recipients)) {
            $subject = "⚠️ HawkerOps Scraper Not Running · {$hoursSinceFormatted}h since last scrape";
            $html = $this->scraperFailureEmailHtml($hoursSinceFormatted, $time, $appUrl);
            $this->sendViaResend($apiKey, $from, $recipients, $subject, $html, 'checkScraperFailure');
        }

        $waNumber = DB::table('configurations')->where('key', 'whatsapp_number')->value('value');
        $waApikey = DB::table('configurations')->where('key', 'whatsapp_apikey')->value('value');
        $this->sendWhatsApp(
            "⚠️ HawkerOps scraper has not run for {$hoursSinceFormatted}h!\nCheck: {$appUrl}/settings/scraper-status",
            $waNumber, $waApikey
        );
    }

    private function scraperFailureEmailHtml(float $hoursSince, string $time, string $appUrl): string
    {
        return "<!DOCTYPE html>
<html>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f1f5f9;padding:32px 16px;'>
  <tr><td align='center'>
    <table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;width:100%;'>
      <tr>
        <td style='background:#d97706;border-radius:12px 12px 0 0;padding:28px 32px;'>
          <p style='margin:0;color:#fef3c7;font-size:13px;font-weight:600;letter-spacing:1px;text-transform:uppercase;'>HawkerOps Alert</p>
          <h1 style='margin:8px 0 0;color:#ffffff;font-size:24px;font-weight:700;'>⚠️ Scraper Not Running</h1>
        </td>
      </tr>
      <tr>
        <td style='background:#ffffff;padding:32px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;'>
          <p style='margin:0 0 16px;color:#374151;font-size:15px;'>
            No scrape data has been received for <strong style='color:#d97706;'>{$hoursSince} hours</strong>.
            The platform scraper may have stopped or failed.
          </p>
          <p style='margin:0 0 24px;color:#64748b;font-size:14px;'>Detected at {$time} SGT</p>
          <a href='{$appUrl}/settings/scraper-status'
             style='display:inline-block;background:#0f172a;color:#ffffff;padding:12px 22px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:700;'>
            Check Scraper Status →
          </a>
        </td>
      </tr>
      <tr>
        <td style='background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:16px 32px;text-align:center;'>
          <p style='margin:0;color:#94a3b8;font-size:12px;'>HawkerOps · Automated monitoring alert</p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>";
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildPlatformRows(array $platformStatuses): string
    {
        $platformColors = [
            'grab'      => '#00b14f',
            'foodpanda' => '#d70f64',
        ];

        $rows = '';
        foreach (self::PLATFORMS as $key) {
            $label   = self::PLATFORM_LABELS[$key] ?? ucfirst($key);
            $isOnline = $platformStatuses[$key] ?? null;
            $color   = $platformColors[$key] ?? '#64748b';

            if ($isOnline === null) continue;

            $badge = $isOnline
                ? "<span style='color:#16a34a;font-weight:700;'>✅ Online</span>"
                : "<span style='color:#dc2626;font-weight:700;'>❌ Offline</span>";

            $rows .= "
                <tr>
                    <td style='padding:10px 14px;border-bottom:1px solid #f1f5f9;'>
                        <span style='display:inline-block;width:10px;height:10px;border-radius:50%;
                              background:{$color};margin-right:8px;'></span>
                        <strong style='color:#1e293b;'>{$label}</strong>
                    </td>
                    <td style='padding:10px 14px;border-bottom:1px solid #f1f5f9;text-align:right;'>{$badge}</td>
                </tr>";
        }
        return $rows;
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $mins  = $minutes % 60;
            return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
        }
        return "{$minutes} min";
    }

    private function sendViaResend(string $apiKey, string $from, array $to, string $subject, string $html, string $logContext): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.resend.com/emails', [
                'from'    => $from,
                'to'      => $to,
                'subject' => $subject,
                'html'    => $html,
            ]);

            if ($response->successful()) {
                Log::info("AlertService: Email sent [{$logContext}]");
                return true;
            }

            Log::error("AlertService: Failed [{$logContext}]", ['response' => $response->body()]);
            return false;

        } catch (\Exception $e) {
            Log::error("AlertService: Exception [{$logContext}]", ['error' => $e->getMessage()]);
            return false;
        }
    }

    // ── Email Templates ───────────────────────────────────────────────────────

    private function offlineEmailHtml(string $shopName, string $time, string $platformRows, string $storeUrl, string $appUrl): string
    {
        return "<!DOCTYPE html>
<html>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f1f5f9;padding:32px 16px;'>
  <tr><td align='center'>
    <table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;width:100%;'>

      <!-- Header -->
      <tr>
        <td style='background:#dc2626;border-radius:12px 12px 0 0;padding:28px 32px;'>
          <p style='margin:0;color:#fecaca;font-size:13px;font-weight:600;letter-spacing:1px;text-transform:uppercase;'>HawkerOps Alert</p>
          <h1 style='margin:8px 0 0;color:#ffffff;font-size:24px;font-weight:700;'>🔴 Store Offline</h1>
        </td>
      </tr>

      <!-- Body -->
      <tr>
        <td style='background:#ffffff;padding:32px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;'>

          <h2 style='margin:0 0 4px;color:#0f172a;font-size:22px;font-weight:700;'>{$shopName}</h2>
          <p style='margin:0 0 24px;color:#64748b;font-size:14px;'>Detected at {$time} SGT</p>

          <p style='margin:0 0 16px;color:#374151;font-size:15px;'>
            All delivery platforms are currently <strong style='color:#dc2626;'>OFFLINE</strong>.
            Action may be required.
          </p>

          <!-- Platform status table -->
          <table width='100%' cellpadding='0' cellspacing='0'
                 style='border:1px solid #e2e8f0;border-radius:8px;border-collapse:separate;
                        border-spacing:0;overflow:hidden;margin-bottom:28px;'>
            <tr style='background:#f8fafc;'>
              <th style='padding:10px 14px;text-align:left;font-size:12px;color:#64748b;
                         font-weight:600;letter-spacing:.5px;text-transform:uppercase;
                         border-bottom:1px solid #e2e8f0;'>Platform</th>
              <th style='padding:10px 14px;text-align:right;font-size:12px;color:#64748b;
                         font-weight:600;letter-spacing:.5px;text-transform:uppercase;
                         border-bottom:1px solid #e2e8f0;'>Status</th>
            </tr>
            {$platformRows}
          </table>

          <!-- CTA Buttons -->
          <table cellpadding='0' cellspacing='0'>
            <tr>
              <td style='padding-right:12px;'>
                <a href='{$storeUrl}'
                   style='display:inline-block;background:#0f172a;color:#ffffff;
                          padding:12px 22px;border-radius:8px;text-decoration:none;
                          font-size:14px;font-weight:700;'>
                  View Store →
                </a>
              </td>
              <td>
                <a href='{$appUrl}/alerts'
                   style='display:inline-block;background:#f1f5f9;color:#475569;
                          padding:12px 22px;border-radius:8px;text-decoration:none;
                          font-size:14px;font-weight:600;border:1px solid #e2e8f0;'>
                  All Alerts
                </a>
              </td>
            </tr>
          </table>

        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style='background:#f8fafc;border:1px solid #e2e8f0;border-top:none;
                   border-radius:0 0 12px 12px;padding:16px 32px;text-align:center;'>
          <p style='margin:0;color:#94a3b8;font-size:12px;'>
            HawkerOps · Automated monitoring alert<br>
            <a href='{$appUrl}/dashboard' style='color:#94a3b8;'>View Dashboard</a>
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>";
    }

    private function recoveryEmailHtml(string $shopName, string $time, string $duration, string $platformRows, string $storeUrl, string $appUrl): string
    {
        return "<!DOCTYPE html>
<html>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f1f5f9;padding:32px 16px;'>
  <tr><td align='center'>
    <table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;width:100%;'>

      <!-- Header -->
      <tr>
        <td style='background:#16a34a;border-radius:12px 12px 0 0;padding:28px 32px;'>
          <p style='margin:0;color:#bbf7d0;font-size:13px;font-weight:600;letter-spacing:1px;text-transform:uppercase;'>HawkerOps Alert</p>
          <h1 style='margin:8px 0 0;color:#ffffff;font-size:24px;font-weight:700;'>✅ Store Recovered</h1>
        </td>
      </tr>

      <!-- Body -->
      <tr>
        <td style='background:#ffffff;padding:32px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;'>

          <h2 style='margin:0 0 4px;color:#0f172a;font-size:22px;font-weight:700;'>{$shopName}</h2>
          <p style='margin:0 0 24px;color:#64748b;font-size:14px;'>Recovered at {$time} SGT</p>

          <!-- Downtime pill -->
          <div style='display:inline-block;background:#fef9c3;border:1px solid #fde047;
                      border-radius:20px;padding:6px 16px;margin-bottom:24px;'>
            <span style='color:#854d0e;font-size:14px;font-weight:600;'>
              ⏱ Total downtime: {$duration}
            </span>
          </div>

          <p style='margin:0 0 16px;color:#374151;font-size:15px;'>
            The store is back <strong style='color:#16a34a;'>ONLINE</strong>. Current platform status:
          </p>

          <!-- Platform status table -->
          <table width='100%' cellpadding='0' cellspacing='0'
                 style='border:1px solid #e2e8f0;border-radius:8px;border-collapse:separate;
                        border-spacing:0;overflow:hidden;margin-bottom:28px;'>
            <tr style='background:#f8fafc;'>
              <th style='padding:10px 14px;text-align:left;font-size:12px;color:#64748b;
                         font-weight:600;letter-spacing:.5px;text-transform:uppercase;
                         border-bottom:1px solid #e2e8f0;'>Platform</th>
              <th style='padding:10px 14px;text-align:right;font-size:12px;color:#64748b;
                         font-weight:600;letter-spacing:.5px;text-transform:uppercase;
                         border-bottom:1px solid #e2e8f0;'>Status</th>
            </tr>
            {$platformRows}
          </table>

          <!-- CTA Buttons -->
          <table cellpadding='0' cellspacing='0'>
            <tr>
              <td style='padding-right:12px;'>
                <a href='{$storeUrl}'
                   style='display:inline-block;background:#0f172a;color:#ffffff;
                          padding:12px 22px;border-radius:8px;text-decoration:none;
                          font-size:14px;font-weight:700;'>
                  View Store →
                </a>
              </td>
              <td>
                <a href='{$appUrl}/alerts'
                   style='display:inline-block;background:#f1f5f9;color:#475569;
                          padding:12px 22px;border-radius:8px;text-decoration:none;
                          font-size:14px;font-weight:600;border:1px solid #e2e8f0;'>
                  All Alerts
                </a>
              </td>
            </tr>
          </table>

        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style='background:#f8fafc;border:1px solid #e2e8f0;border-top:none;
                   border-radius:0 0 12px 12px;padding:16px 32px;text-align:center;'>
          <p style='margin:0;color:#94a3b8;font-size:12px;'>
            HawkerOps · Automated monitoring alert<br>
            <a href='{$appUrl}/dashboard' style='color:#94a3b8;'>View Dashboard</a>
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>";
    }
}
