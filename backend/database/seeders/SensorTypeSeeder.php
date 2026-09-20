<?php

namespace Database\Seeders;

use App\Models\SensorType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SensorTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'temp_air', 'unit' => '°C', 'min_value' => -50, 'max_value' => 60, 'precision' => 1],
            ['name' => 'humidity', 'unit' => '%', 'min_value' => 0, 'max_value' => 100, 'precision' => 1],
            ['name' => 'pressure', 'unit' => 'hPa', 'min_value' => 800, 'max_value' => 1100, 'precision' => 1],
            ['name' => 'wind_speed', 'unit' => 'm/s', 'min_value' => 0, 'max_value' => 75, 'precision' => 1],
            ['name' => 'wind_dir', 'unit' => '°', 'min_value' => 0, 'max_value' => 359, 'precision' => 0],
            ['name' => 'rain_counter', 'unit' => 'mm', 'min_value' => 0, 'max_value' => 4294967295, 'precision' => 0],
            ['name' => 'solar_rad', 'unit' => 'W/m²', 'min_value' => 0, 'max_value' => 1500, 'precision' => 1],
        ];

        foreach ($types as $type) {
            SensorType::create(array_merge(['id' => Str::uuid()->toString()], $type));
        }
    }
}
