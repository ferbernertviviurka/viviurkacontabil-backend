<?php

namespace App\Console\Commands;

use App\Services\MonthlyPaymentService;
use Illuminate\Console\Command;

class GenerateMonthlyPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly payments for active subscriptions';

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
        $this->info('Generating monthly payments...');

        $count = $this->monthlyPaymentService->generateMonthlyPayments();

        $this->info("Generated {$count} monthly payments.");

        return Command::SUCCESS;
    }
}

