<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AutoMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:auto {--seed : Run seeders after migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations and optionally seeders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->info(Artisan::output());

        if ($this->option('seed')) {
            $this->info('Running seeders...');
            Artisan::call('db:seed', ['--force' => true]);
            $this->info(Artisan::output());
        }

        $this->info('Done!');
        return 0;
    }
}

