<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupCommand extends Command
{
    protected $signature = 'app:setup {--force : Skip confirmation}';

    protected $description = 'Run migrations, seeders, and create TimescaleDB aggregates';

    public function handle(): int
    {
        $this->info('=== Database Setup ===');

        $this->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->info(Artisan::output());

        $this->info('Seeding database...');
        Artisan::call('db:seed', ['--force' => true]);
        $this->info(Artisan::output());

        $this->info('=== TimescaleDB Aggregates ===');
        Artisan::call('timescale:create-aggregates', ['--drop' => true]);
        $this->info(Artisan::output());

        $this->info('Setup complete!');
        return self::SUCCESS;
    }
}
