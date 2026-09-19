<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_calibrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sensor_id')->constrained('sensors');
            $table->decimal('offset', 10, 5);
            $table->decimal('scale', 10, 5);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable()->comment('null jika masih aktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_calibrations');
    }
};
