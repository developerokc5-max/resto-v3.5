<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AlertService
{
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
            $shopName     = $platforms->first()->shop_name ?? $shopId;
            $totalCount   = $platforms->count();
            $offlineCount = $platforms->where('is_online', false)->count();
            $allOffline   = $offlineCount === $totalCount && $totalCount > 0;

            $openAlert = $openAlerts->get($shopId);

            if ($allOffline && !$openAlert) {
                // Store just went fully offline — create alert + send email
                $offlinePlatforms = $platforms->where('is_online', false)
                    ->pluck('platform')->toArray();

                $alertId = DB::table('alert_logs')->insertGetId([
                    'shop_id'           => $shopId,
                    'shop_name'         => $shopName,
                    'type'              => 'offline',
                    'platforms_affected'=> json_encode($offlinePlatforms),
                    'alerted_at'        => Carbon::now(),
                    'email_sent'        => false,
                    'created_at'        => Carbon::now(),
                    'updated_at'        => Carbon::now(),
                ]);

                $sent = $this->sendOfflineEmail($shopName, $offlinePlatforms, $recipients);

                DB::table('alert_logs')->where('id', $alertId)
                    ->update(['email_sent' => $sent]);

            } elseif (!$allOffline && $openAlert) {
                // Store recovered — close alert + send recovery email
                $downtimeMinutes = Carbon::parse($openAlert->alerted_at)
                    ->diffInMinutes(Carbon::now());

                DB::table('alert_logs')->where('id', $openAlert->id)->update([
                    'recovered_at'    => Carbon::now(),
                    'downtime_minutes'=> $downtimeMinutes,
                    'updated_at'      => Carbon::now(),
                ]);

                $this->sendRecoveryEmail($shopName, $downtimeMinutes, $recipients);
            }
        }
    }

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

    private function sendOfflineEmail(string $shopName, array $platforms, array $to): bool
    {
        $apiKey  = env('RESEND_API_KEY');
        $from    = env('ALERT_FROM_EMAIL', 'onboarding@resend.dev');

        if (!$apiKey) {
            Log::warning('AlertService: RESEND_API_KEY not set');
            return false;
        }

        $time         = Carbon::now('Asia/Singapore')->format('d M Y, h:i A');
        $platformList = implode(', ', array_map('ucfirst', $platforms));
        $appUrl       = env('APP_URL', 'https://resto-v3-5.onrender.com');

        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #dc2626; padding: 20px; border-radius: 8px 8px 0 0;'>
                <h1 style='color: white; margin: 0; font-size: 20px;'>🚨 Store Offline Alert</h1>
            </div>
            <div style='background: #fff; padding: 24px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;'>
                <h2 style='color: #111; margin-top: 0;'>{$shopName}</h2>
                <p style='color: #374151;'>All platforms are currently <strong style='color: #dc2626;'>OFFLINE</strong>.</p>
                <table style='width: 100%; border-collapse: collapse; margin: 16px 0;'>
                    <tr style='background: #f9fafb;'>
                        <td style='padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;'>Platforms Offline</td>
                        <td style='padding: 10px; border: 1px solid #e5e7eb; color: #dc2626;'>{$platformList}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;'>Detected At</td>
                        <td style='padding: 10px; border: 1px solid #e5e7eb;'>{$time} (SGT)</td>
                    </tr>
                </table>
                <a href='{$appUrl}/alerts'
                   style='display: inline-block; background: #111; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;'>
                   View Alerts Dashboard
                </a>
            </div>
        </div>";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.resend.com/emails', [
                'from'    => $from,
                'to'      => $to,
                'subject' => "🚨 {$shopName} — All Platforms Offline",
                'html'    => $html,
            ]);

            if ($response->successful()) {
                Log::info("AlertService: Offline alert sent for {$shopName}");
                return true;
            }

            Log::error('AlertService: Failed to send email', ['response' => $response->body()]);
            return false;

        } catch (\Exception $e) {
            Log::error('AlertService: Exception sending email', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function sendRecoveryEmail(string $shopName, int $downtimeMinutes, array $to): bool
    {
        $apiKey  = env('RESEND_API_KEY');
        $from    = env('ALERT_FROM_EMAIL', 'onboarding@resend.dev');

        if (!$apiKey) return false;

        $time     = Carbon::now('Asia/Singapore')->format('d M Y, h:i A');
        $duration = $downtimeMinutes >= 60
            ? round($downtimeMinutes / 60, 1) . ' hours'
            : $downtimeMinutes . ' minutes';
        $appUrl   = env('APP_URL', 'https://resto-v3-5.onrender.com');

        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #16a34a; padding: 20px; border-radius: 8px 8px 0 0;'>
                <h1 style='color: white; margin: 0; font-size: 20px;'>✅ Store Recovered</h1>
            </div>
            <div style='background: #fff; padding: 24px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;'>
                <h2 style='color: #111; margin-top: 0;'>{$shopName}</h2>
                <p style='color: #374151;'>Store is back <strong style='color: #16a34a;'>ONLINE</strong>.</p>
                <table style='width: 100%; border-collapse: collapse; margin: 16px 0;'>
                    <tr style='background: #f9fafb;'>
                        <td style='padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;'>Recovered At</td>
                        <td style='padding: 10px; border: 1px solid #e5e7eb;'>{$time} (SGT)</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;'>Total Downtime</td>
                        <td style='padding: 10px; border: 1px solid #e5e7eb; color: #d97706;'>{$duration}</td>
                    </tr>
                </table>
                <a href='{$appUrl}/alerts'
                   style='display: inline-block; background: #111; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;'>
                   View Alerts Dashboard
                </a>
            </div>
        </div>";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.resend.com/emails', [
                'from'    => $from,
                'to'      => $to,
                'subject' => "✅ {$shopName} — Back Online (was down {$duration})",
                'html'    => $html,
            ]);

            if ($response->successful()) {
                Log::info("AlertService: Recovery alert sent for {$shopName}");
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('AlertService: Exception sending recovery email', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
