<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Illuminate\Console\Command;

class SendDailySummary extends Command
{
    protected $signature   = 'alerts:daily-summary';
    protected $description = 'Send the daily HawkerOps summary email and WhatsApp message';

    public function handle(AlertService $alertService): int
    {
        $alertService->sendDailySummary();
        $this->info('Daily summary sent.');
        return Command::SUCCESS;
    }
}
