<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule: Generate monthly payments (daily at 00:00)
Schedule::command('payments:generate')->daily();

// Schedule: Send payment reminders (daily at 08:00)
Schedule::command('payments:send-reminders')->dailyAt('08:00');

// Schedule: Cleanup expired charges (every 5 minutes)
// Delete pending PIX and Credit Card charges older than 10 minutes
Schedule::command('charges:cleanup-expired')->everyFiveMinutes();
