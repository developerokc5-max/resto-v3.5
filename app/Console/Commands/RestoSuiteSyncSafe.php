<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RestoSuiteSyncSafe extends Command
{
    protected $signature = 'restosuite:sync-safe {--page=1} {--size=100}';
    protected $description = 'Safe sync wrapper: prevents overlapping and avoids token spam (openapi-code=5).';

    public function handle(): int
    {
        $page = (int) $this->option('page');
        $size = (int) $this->option('size');

        // One global lock to ensure scheduler + manual runs never overlap
        $lockKey = 'restosuite.sync.lock';
        $lockTtlSeconds = (int) (env('RESTOSUITE_SYNC_LOCK_SECONDS', 600)); // 10 min default

        $lock = Cache::lock($lockKey, $lockTtlSeconds);

        if (! $lock->get()) {
            $this->info('Sync already running (lock active). Skipping.');
            return self::SUCCESS;
        }

        try {
            // If you just got openapi-code=5, don’t retry immediately.
            // We put a short cooldown to avoid hammering token endpoint.
            $cooldownKey = 'restosuite.token.cooldown_until';
            $cooldownUntil = (int) Cache::get($cooldownKey, 0);

            if (time() < $cooldownUntil) {
                $this->info('In token cooldown window. Skipping.');
                return self::SUCCESS;
            }

            $this->info("Calling restosuite:sync-items (page={$page}, size={$size}) ...");

            // Call your existing command
            $exit = $this->call('restosuite:sync-items', [
                '--page' => $page,
                '--size' => $size,
            ]);

            return $exit;
        } catch (Throwable $e) {
            // If your client throws "Forbidden to get token frequently"
            // set a cooldown so scheduler stops spamming.
            $msg = $e->getMessage();

            if (str_contains($msg, 'Forbidden to get token frequently') || str_contains($msg, 'openapi-code=5')) {
                // 2 minutes cooldown (adjustable)
                $seconds = (int) env('RESTOSUITE_TOKEN_COOLDOWN_SECONDS', 120);
                Cache::put('restosuite.token.cooldown_until', time() + $seconds, $seconds);
                $this->warn("Token rate limited. Cooldown {$seconds}s set. Not failing the job.");
                return self::SUCCESS;
            }

            $this->error($msg);
            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }
    }
}
