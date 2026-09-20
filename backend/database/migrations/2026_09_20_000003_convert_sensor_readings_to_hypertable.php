<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("SELECT create_hypertable('sensor_readings', 'device_ts', chunk_time_interval => INTERVAL '1 day', if_not_exists => TRUE)");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("SELECT convert_from_regular_table('sensor_readings', if_exists => TRUE)");
    }
};
