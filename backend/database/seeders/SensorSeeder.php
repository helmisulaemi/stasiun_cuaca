<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Sensor;
use App\Models\SensorInstallation;
use App\Models\SensorType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SensorSeeder extends Seeder
{
    public function run(): void
    {
        $devices = Device::where('name','BDG-TIRRA-001')->get();
        $sensorTypes = SensorType::all();

        foreach ($devices as $device) {
            foreach ($sensorTypes as $sensorType) {
                $sensor = Sensor::create([
                    'id' => Str::uuid()->toString(),
                    'sensor_type_id' => $sensorType->id,
                    'serial_number' => strtoupper($device->name) . '-' . strtoupper($sensorType->name),
                    'model' => $this->getModelForType($sensorType->name),
                ]);

                SensorInstallation::create([
                    'id' => Str::uuid()->toString(),
                    'device_id' => $device->id,
                    'sensor_id' => $sensor->id,
                    'installed_at' => now()->subDays(30),
                ]);
            }
        }
    }

    protected function getModelForType(string $type): string
    {
        return match ($type) {
            'temp_air', 'humidity' => 'DHT22',
            'pressure' => 'BMP280',
            'wind_speed', 'wind_dir' => 'DAVIS-6410',
            'rain_counter' => 'TB-01',
            'solar_rad' => 'SQ-500',
            default => 'UNKNOWN',
        };
    }
}
