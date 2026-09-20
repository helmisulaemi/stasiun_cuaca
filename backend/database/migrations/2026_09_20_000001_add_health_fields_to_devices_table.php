<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('secret_hash');
            $table->float('last_battery_v')->nullable()->after('last_seen_at');
            $table->integer('last_rssi')->nullable()->after('last_battery_v');
            $table->string('fw_version')->nullable()->after('last_rssi');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'last_seen_at',
                'last_battery_v',
                'last_rssi',
                'fw_version',
            ]);
        });
    }
};
