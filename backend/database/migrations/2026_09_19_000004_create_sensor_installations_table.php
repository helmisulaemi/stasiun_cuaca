<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_installations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')->constrained('devices');
            $table->foreignUuid('sensor_id')->constrained('sensors');
            $table->timestamp('installed_at');
            $table->timestamp('removed_at')->nullable()->comment('null jika masih terpasang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_installations');
    }
};
