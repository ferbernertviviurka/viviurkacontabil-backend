<?php

namespace App\Console\Commands;

use App\Services\MonthlyPaymentService;
use Illuminate\Console\Command;

class SendMonthlyPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment reminders 5 days before due date';

    protected MonthlyPaymentService $monthlyPaymentService;

    public function __construct(MonthlyPaymentService $monthlyPaymentService)
    {
        parent::__construct();
        $this->monthlyPaymentService = $monthlyPaymentService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending payment reminders...');

        $count = $this->monthlyPaymentService->sendPaymentReminders();

        $this->info("Sent {$count} payment reminders.");

        return Command::SUCCESS;
    }
}

