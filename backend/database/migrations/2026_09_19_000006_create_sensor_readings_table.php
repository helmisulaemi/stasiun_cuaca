<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->uuid('id');
            $table->foreignUuid('sensor_id')->constrained('sensors');
            $table->foreignUuid('device_id')->constrained('devices');
            $table->timestampTz('device_ts')->comment('timestamp dari payload');
            $table->timestampTz('server_ts')->comment('waktu diterima server');
            $table->integer('seq');
            $table->float('raw_value');
            $table->float('calibrated_value');
            $table->string('quality_flag');
            $table->float('battery_v');
            $table->integer('rssi');
            $table->string('firmware');

            $table->primary(['id', 'device_ts']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
