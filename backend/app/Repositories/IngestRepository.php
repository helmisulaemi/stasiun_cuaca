<?php

namespace App\Repositories;

use App\Interfaces\IngestRepositoryInterface;
use App\Models\Device;
use App\Models\SensorCalibration;
use App\Models\SensorInstallation;
use App\Models\SensorType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IngestRepository implements IngestRepositoryInterface
{
    public function bulkInsertReadings(array $readings): array
    {
        if (empty($readings)) {
            return ['accepted' => 0, 'duplicates' => 0];
        }

        $accepted = 0;
        $duplicates = 0;
        $chunkSize = (int) config('app.ingest_chunk_size', 100);

        $chunks = array_chunk($readings, $chunkSize);

        foreach ($chunks as $chunk) {
            $rows = array_map(function ($reading) {
                return [
                    'id' => Str::uuid()->toString(),
                    'sensor_id' => $reading['sensor_id'],
                    'device_id' => $reading['device_id'],
                    'device_ts' => $reading['device_ts'],
                    'server_ts' => now('UTC'),
                    'seq' => $reading['seq'],
                    'raw_value' => $reading['raw_value'],
                    'calibrated_value' => $reading['calibrated_value'],
                    'quality_flag' => $reading['quality_flag'],
                    'battery_v' => $reading['battery_v'],
                    'rssi' => $reading['rssi'],
                    'firmware' => $reading['firmware'],
                    'rain_mm' => $reading['rain_mm'] ?? null,
                ];
            }, $chunk);

            $inserted = DB::table('sensor_readings')
                ->insertOrIgnore($rows);

            $accepted += $inserted;
            $duplicates += count($rows) - $inserted;
        }

        return ['accepted' => $accepted, 'duplicates' => $duplicates];
    }

    public function findSensorTypeByName(string $name): ?SensorType
    {
        return SensorType::where('name', $name)->first();
    }

    public function findActiveInstallationByDeviceAndType(string $deviceId, string $sensorTypeId): ?SensorInstallation
    {
        return SensorInstallation::where('device_id', $deviceId)
            ->whereHas('sensor', fn ($q) => $q->where('sensor_type_id', $sensorTypeId))
            ->whereNull('removed_at')
            ->first();
    }

    public function findActiveCalibration(string $sensorId, Carbon $ts): ?SensorCalibration
    {
        return SensorCalibration::where('sensor_id', $sensorId)
            ->where('effective_from', '<=', $ts)
            ->where(function ($query) use ($ts) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', $ts);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    public function findLastRainCounterByDevice(string $deviceId, string $sensorId, Carbon $currentTs): ?object
    {
        return DB::table('sensor_readings')
            ->where('device_id', $deviceId)
            ->where('sensor_id', $sensorId)
            ->where('device_ts', '<', $currentTs) // untuk race condition
            ->orderBy('device_ts', 'desc')
            ->orderBy('id', 'desc')
            ->select('raw_value', 'device_ts')
            ->first();
    }

    public function updateDeviceHealth(string $deviceId, array $data): void
    {
        Device::where('id', $deviceId)->update($data);
    }
}
