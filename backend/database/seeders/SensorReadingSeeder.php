<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\SensorType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SensorReadingSeeder extends Seeder
{
    public function run(): void
    {
        $devices = Device::all();
        $sensorTypes = SensorType::all()->keyBy('name');

        $now = now('UTC');
        $startDate = $now->copy()->subDays(7)->startOfHour();
        $intervalMinutes = 1;
        $totalReadings = 0;

        foreach ($devices as $device) {
            if ($device->status === 'maintenance') {
                $this->seedMaintenanceDevice($device, $sensorTypes, $startDate, $now);
                $totalReadings += $this->getReadingCount($startDate, $now->copy()->subDays(2), $intervalMinutes);
                continue;
            }

            $rainCounter = 0;
            $prevRainCounter = null;

            $current = $startDate->copy();
            while ($current->lessThan($now)) {
                $readings = $this->generateRealisticReadings($current->hour, $current->month, $rainCounter);

                // Inject occasional bad readings (1% SENSOR_ERROR on temp, 2% OUT_OF_RANGE on humidity)
                if (mt_rand(1, 100) <= 1) {
                    $readings['temp_air'] = -999;
                } elseif (mt_rand(1, 100) <= 2) {
                    $readings['humidity'] = 150;
                }

                foreach ($readings as $sensorCode => $value) {
                    $sensorType = $sensorTypes->get($sensorCode);
                    if (! $sensorType) {
                        continue;
                    }

                    $sensor = Sensor::where('sensor_type_id', $sensorType->id)
                        ->whereHas('installations', fn ($q) => $q->where('device_id', $device->id))
                        ->first();

                    if (! $sensor) {
                        continue;
                    }

                    $qualityFlag = $this->determineQualityFlag($sensorCode, $value, $sensorType);

                    $rainMm = 0.0;
                    if ($sensorCode === 'rain_counter' && $prevRainCounter !== null) {
                        $delta = ($value < $prevRainCounter) ? $value : ($value - $prevRainCounter);
                        $rainMm = round($delta * 0.2, 4);
                    }

                    SensorReading::create([
                        'id' => Str::uuid()->toString(),
                        'sensor_id' => $sensor->id,
                        'device_id' => $device->id,
                        'device_ts' => $current->copy()->toDateTimeString(),
                        'server_ts' => $current->copy()->addSeconds(rand(1, 5))->toDateTimeString(),
                        'seq' => $current->timestamp - $startDate->timestamp,
                        'raw_value' => $value,
                        'calibrated_value' => $value,
                        'rain_mm' => $rainMm,
                        'quality_flag' => $qualityFlag,
                        'battery_v' => round(3.8 + (mt_rand(0, 40) / 100), 2),
                        'rssi' => mt_rand(-80, -50),
                        'firmware' => '1.4.2',
                    ]);
                }

                $prevRainCounter = $rainCounter;
                if (isset($readings['rain_counter'])) {
                    $rainCounter = $readings['rain_counter'];
                }

                $current->addMinutes($intervalMinutes);
                $totalReadings++;
            }
        }

        $this->command->info("Seeded {$totalReadings} reading intervals across " . $devices->count() . " devices.");
    }

    protected function seedMaintenanceDevice(Device $device, $sensorTypes, $startDate, $now): void
    {
        $current = $startDate->copy();
        $endDate = $now->copy()->subDays(2);

        while ($current->lessThan($endDate)) {
            $readings = $this->generateRealisticReadings($current->hour, $current->month, 0);

            foreach ($readings as $sensorCode => $value) {
                $sensorType = $sensorTypes->get($sensorCode);
                if (! $sensorType) {
                    continue;
                }

                $sensor = Sensor::where('sensor_type_id', $sensorType->id)
                    ->whereHas('installations', fn ($q) => $q->where('device_id', $device->id))
                    ->first();

                if (! $sensor) {
                    continue;
                }

                SensorReading::create([
                    'id' => Str::uuid()->toString(),
                    'sensor_id' => $sensor->id,
                    'device_id' => $device->id,
                    'device_ts' => $current->copy()->toDateTimeString(),
                    'server_ts' => $current->copy()->addSeconds(rand(1, 5))->toDateTimeString(),
                    'seq' => $current->timestamp - $startDate->timestamp,
                    'raw_value' => $value,
                    'calibrated_value' => $value,
                    'quality_flag' => $this->determineQualityFlag($sensorCode, $value, $sensorType),
                    'battery_v' => round(3.4 + (mt_rand(0, 10) / 100), 2),
                    'rssi' => mt_rand(-90, -75),
                    'firmware' => '1.3.8',
                ]);
            }

            $current->addMinutes(1);
        }
    }

    protected function generateRealisticReadings(int $hour, int $month, float $rainCounter): array
    {
        $isSummer = in_array($month, [4, 5, 6, 7, 8, 9]);
        $isNight = $hour >= 19 || $hour <= 5;
        $isMorning = $hour >= 6 && $hour <= 10;
        $isAfternoon = $hour >= 11 && $hour <= 16;

        if ($isNight) {
            $tempBase = 22;
        } elseif ($isMorning) {
            $tempBase = 26;
        } elseif ($isAfternoon) {
            $tempBase = $isSummer ? 33 : 29;
        } else {
            $tempBase = 25;
        }
        $tempAir = round($tempBase + (mt_rand(-20, 20) / 10), 1);

        if ($isNight) {
            $humBase = 85;
        } elseif ($isAfternoon) {
            $humBase = 60;
        } else {
            $humBase = 75;
        }
        $humidity = round($humBase + (mt_rand(-100, 100) / 10), 1);
        $humidity = max(40, min(100, $humidity));

        $pressure = round(1010 + (mt_rand(-50, 50) / 10), 1);

        if ($isAfternoon) {
            $windBase = 4 + ($isSummer ? 2 : 0);
        } else {
            $windBase = 2;
        }
        $windSpeed = round(max(0, $windBase + (mt_rand(-20, 30) / 10)), 1);

        $windDir = mt_rand(0, 359);

        $isRaining = mt_rand(1, 100) <= 15;
        if ($isRaining && $rainCounter < 4294967295) {
            $tips = mt_rand(1, 5);
            $rainCounter += $tips;
        }

        if ($isNight) {
            $solarRad = 0;
        } elseif ($isMorning || ($hour >= 17 && $hour <= 18)) {
            $solarRad = mt_rand(100, 600);
        } else {
            $solarRad = mt_rand(400, 1200);
        }

        return [
            'temp_air' => $tempAir,
            'humidity' => $humidity,
            'pressure' => $pressure,
            'wind_speed' => $windSpeed,
            'wind_dir' => $windDir,
            'rain_counter' => $rainCounter,
            'solar_rad' => $solarRad,
        ];
    }

    protected function determineQualityFlag(string $sensorCode, float $value, SensorType $sensorType): string
    {
        if ($sensorCode === 'temp_air' && $value == -999) {
            return 'SENSOR_ERROR';
        }

        if ($value < $sensorType->min_value || $value > $sensorType->max_value) {
            return 'OUT_OF_RANGE';
        }

        return 'OK';
    }

    protected function getReadingCount($start, $end, int $intervalMinutes): int
    {
        $minutes = $start->diffInMinutes($end);
        return (int) ceil($minutes / $intervalMinutes);
    }
}
