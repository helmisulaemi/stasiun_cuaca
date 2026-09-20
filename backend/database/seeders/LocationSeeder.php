<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Gardu Pantai Bandung',
                'latitude' => -6.1234,
                'longitude' => 106.1234,
                'altitude' => 10,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Stasiun BMKG Jakarta',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'altitude' => 5,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Pelabuhan Tanjung Perak Surabaya',
                'latitude' => -7.2575,
                'longitude' => 112.7521,
                'altitude' => 3,
            ],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}
