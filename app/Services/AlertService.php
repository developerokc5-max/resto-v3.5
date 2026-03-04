<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlertService
{
    // Platform display config
    private const PLATFORMS = ['grab', 'foodpanda', 'deliveroo'];

    private const PLATFORM_LABELS = [
        'grab'      => 'Grab',
        'foodpanda' => 'FoodPanda',
        'deliveroo' => 'Deliveroo',
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

        // Resolve recipients once for the whole run
        $recipients = $this->getRecipients();

        foreach ($currentStatuses as $shopId => $platforms) {
            $shopName     = $platforms->first()->shop_name ?? 'Unknown Store';
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

                $sent = $this->sendOfflineEmail($shopId, $shopName, $platformStatuses, $recipients);

                DB::table('alert_logs')->where('id', $alertId)
                    ->update(['email_sent' => $sent]);

            } elseif (!$allOffline && $openAlert) {
                // Store recovered — close alert + send recovery email
                $downtimeMinutes = Carbon::parse($openAlert->alerted_at)
                    ->diffInMinutes(Carbon::now());

                DB::table('alert_logs')->where('id', $openAlert->id)->update([
                    'recovered_at'     => Carbon::now(),
                    'downtime_minutes' => $downtimeMinutes,
                    'updated_at'       => Carbon::now(),
                ]);

                $this->sendRecoveryEmail($shopId, $shopName, $platformStatuses, $downtimeMinutes, $recipients);
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
        return ['developerokc5@gmail.com'];
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

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildPlatformRows(array $platformStatuses): string
    {
        $platformColors = [
            'grab'      => '#00b14f',
            'foodpanda' => '#d70f64',
            'deliveroo' => '#00ccbc',
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
