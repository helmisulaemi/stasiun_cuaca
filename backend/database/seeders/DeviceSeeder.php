<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $apiKey1 = 'ws-bandung-001-key';
        $apiKey2 = 'ws-jakarta-001-key';
        $apiKey3 = 'ws-surabaya-001-key';

        $bandung = Location::where('name', 'Gardu Pantai Bandung')->first();
        $jakarta = Location::where('name', 'Stasiun BMKG Jakarta')->first();
        $surabaya = Location::where('name', 'Pelabuhan Tanjung Perak Surabaya')->first();

        Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'WS-BDO-001',
            'location_id' => $bandung->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', $apiKey1),
            'last_seen_at' => now()->subMinutes(5),
            'last_battery_v' => 3.92,
            'last_rssi' => -71,
            'fw_version' => '1.4.2',
        ]);

        Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'JKT-BMKG-001',
            'location_id' => $jakarta->id,
            'status' => 'active',
            'secret_hash' => hash('sha256', $apiKey2),
            'last_seen_at' => now()->subMinutes(2),
            'last_battery_v' => 4.05,
            'last_rssi' => -65,
            'fw_version' => '1.4.2',
        ]);

        Device::create([
            'id' => Str::uuid()->toString(),
            'name' => 'SBY-PLN-001',
            'location_id' => $surabaya->id,
            'status' => 'maintenance',
            'secret_hash' => hash('sha256', $apiKey3),
            'last_seen_at' => now()->subDays(2),
            'last_battery_v' => 3.45,
            'last_rssi' => -85,
            'fw_version' => '1.3.8',
        ]);
    }
}
