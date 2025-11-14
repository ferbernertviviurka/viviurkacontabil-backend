<?php

namespace App\Console\Commands;

use App\Models\Boleto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupExpiredCharges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charges:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete pending charges (PIX and Credit Card) older than 10 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up expired charges...');

        // Get charges created more than 10 minutes ago
        $expiredDate = Carbon::now()->subMinutes(10);

        // Delete pending charges of type PIX or credit_card created more than 10 minutes ago
        $deleted = Boleto::where('status', 'pending')
            ->whereIn('tipo_pagamento', ['pix', 'credit_card'])
            ->where('created_at', '<', $expiredDate)
            ->delete();

        $this->info("Deleted {$deleted} expired charges.");

        if ($deleted > 0) {
            Log::info('Expired charges cleaned up', ['count' => $deleted]);
        }

        return Command::SUCCESS;
    }
}
