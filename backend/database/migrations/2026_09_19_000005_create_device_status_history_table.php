<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')->constrained('devices');
            $table->string('status');
            $table->timestamp('changed_at');
            $table->foreignUuid('changed_by_user_id')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_status_history');
    }
};
