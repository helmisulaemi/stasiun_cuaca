<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateContinuousAggregates extends Command
{
    protected $signature = 'timescale:create-aggregates {--drop : Drop existing views first}';

    protected $description = 'Create TimescaleDB continuous aggregate views (1m, 1h, 1d) with refresh policies';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->warn('Not PostgreSQL — skipping.');
            return self::SUCCESS;
        }

        if ($this->option('drop')) {
            $this->info('Dropping existing views...');
            DB::unprepared("DROP MATERIALIZED VIEW IF EXISTS reading_agg_1d_mv");
            DB::unprepared("DROP MATERIALIZED VIEW IF EXISTS reading_agg_1h_mv");
            DB::unprepared("DROP MATERIALIZED VIEW IF EXISTS reading_agg_1m_mv");
        }

        $views = [
            '1m' => '1 minute',
            '1h' => '1 hour',
            '1d' => '1 day',
        ];

        foreach ($views as $key => $interval) {
            $viewName = "reading_agg_{$key}_mv";
            $this->info("Creating {$viewName} (time_bucket {$interval})...");

            DB::unprepared("
                CREATE MATERIALIZED VIEW IF NOT EXISTS {$viewName}
                WITH (timescaledb.continuous) AS
                SELECT
                    time_bucket('{$interval}', device_ts) AS interval_start,
                    device_id,
                    sensor_type_id,
                    AVG(calibrated_value) AS avg_value,
                    MIN(calibrated_value) AS min_value,
                    MAX(calibrated_value) AS max_value,
                    COUNT(*) AS sample_count
                FROM sensor_readings sr
                JOIN sensors s ON s.id = sr.sensor_id
                WHERE sr.quality_flag = 'OK'
                GROUP BY interval_start, device_id, sensor_type_id
            ");
        }

        $policies = [
            'reading_agg_1m_mv' => ['1 hour', '1 minute', '1 minute'],
            'reading_agg_1h_mv' => ['1 day', '1 hour', '1 hour'],
            'reading_agg_1d_mv' => ['7 days', '1 day', '1 day'],
        ];

        foreach ($policies as $view => [$start, $end, $schedule]) {
            $this->info("Adding policy to {$view}...");
            DB::unprepared("
                SELECT add_continuous_aggregate_policy('{$view}',
                    start_offset      => INTERVAL '{$start}',
                    end_offset        => INTERVAL '{$end}',
                    schedule_interval => INTERVAL '{$schedule}')
            ");
        }

        $this->info('Done! Continuous aggregates created.');
        return self::SUCCESS;
    }
}
