<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LocationSeeder::class,
            SensorTypeSeeder::class,
            DeviceSeeder::class,
            SensorSeeder::class,
            SensorReadingSeeder::class,
        ]);
    }
}
