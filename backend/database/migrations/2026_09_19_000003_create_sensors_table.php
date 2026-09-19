<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sensor_type_id')->constrained('sensor_types');
            $table->string('serial_number')->nullable()->comment('opsional');
            $table->string('model');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};
