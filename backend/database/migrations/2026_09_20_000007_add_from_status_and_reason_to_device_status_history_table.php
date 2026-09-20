<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_status_history', function (Blueprint $table) {
            $table->string('from_status')->nullable()->after('device_id');
            $table->string('reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('device_status_history', function (Blueprint $table) {
            $table->dropColumn(['from_status', 'reason']);
        });
    }
};
