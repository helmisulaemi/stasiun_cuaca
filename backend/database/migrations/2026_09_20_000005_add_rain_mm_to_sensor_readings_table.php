<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->float('rain_mm')->nullable()->after('calibrated_value')
                ->comment('derived: delta × 0.2, hanya untuk rain_counter');
        });
    }

    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->dropColumn('rain_mm');
        });
    }
};
