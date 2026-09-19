<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->comment('contoh: temp_air, humidity');
            $table->string('unit');
            $table->decimal('min_value', 10, 5);
            $table->decimal('max_value', 10, 5);
            $table->integer('precision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_types');
    }
};
