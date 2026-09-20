<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Interfaces\IngestRepositoryInterface;
use App\Models\Device;
use Carbon\Carbon;

class IngestService
{
    public function __construct(
        protected IngestRepositoryInterface $ingestRepository,
    ) {}

    public function processSingle(Device $device, array $data): array
    {
        $deviceTs = Carbon::createFromTimestamp($data['ts'], 'UTC');
        $clockFutureTolerance = (int) config('ingest.clock_future_tolerance', 300);

        // ts in future check (EC-1)
        if ($deviceTs->gt(now('UTC')->addSeconds($clockFutureTolerance))) {
            throw new BusinessException(422, 'TS_IN_FUTURE', 'Timestamp terlalu jauh di masa depan.');
        }

        // device_id mismatch check (EC-6)
        if (isset($data['device_id']) && $data['device_id'] !== $device->name) {
            throw new BusinessException(403, 'FORBIDDEN', 'Device ID tidak sesuai.');
        }

        $readings = $data['readings'];
        $items = [];
        $accepted = 0;
        $duplicates = 0;
        $rejected = 0;
        $rowsToInsert = [];

        foreach ($readings as $index => $reading) {
            $sensorCode = $reading['s'];
            $rawValue = $reading['v'];

            // Find sensor type via repository
            $sensorType = $this->ingestRepository->findSensorTypeByName($sensorCode);
            if (! $sensorType) {
                $items[] = ['index' => $index, 's' => $sensorCode, 'status' => 'skipped', 'reason' => 'unknown_sensor_type'];
                continue;
            }

            // Find installed sensor on this device via repository
            $installation = $this->ingestRepository->findActiveInstallationByDeviceAndType($device->id, $sensorType->id);
            if (! $installation) {
                $items[] = ['index' => $index, 's' => $sensorCode, 'status' => 'skipped', 'reason' => 'not_installed'];
                continue;
            }

            $sensorId = $installation->sensor_id;

            // Quality check (EC-1: CLOCK_SKEW, EC-2: SENSOR_ERROR, EC-3: OUT_OF_RANGE)
            $qualityFlag = $this->checkQuality($rawValue, $sensorType, $sensorCode);

            // Check for clock skew (ts within tolerance but in the future)
            if ($deviceTs->gt(now('UTC')) && $qualityFlag === 'OK') {
                $qualityFlag = 'CLOCK_SKEW';
            }

            // Calibration via repository
            $calibrated = $this->applyCalibration($sensorId, $rawValue, $deviceTs);

            // rain_mm: hanya untuk rain_counter, null untuk sensor lain (EC-4)
            $rainMm = ($sensorCode === 'rain_counter')
                ? $this->calculateRainMm($device->id, $sensorId, $rawValue, $deviceTs)
                : null;

            $rowsToInsert[] = [
                'sensor_id' => $sensorId,
                'device_id' => $device->id,
                'device_ts' => $deviceTs,
                'raw_value' => $rawValue,
                'calibrated_value' => $calibrated,
                'quality_flag' => $qualityFlag,
                'seq' => $data['seq'],
                'battery_v' => $data['battery_v'],
                'rssi' => $data['rssi'],
                'firmware' => $data['fw'],
                'rain_mm' => $rainMm,
            ];

            $items[] = ['index' => $index, 's' => $sensorCode, 'status' => 'accepted'];
            $accepted++;
        }

        // Bulk insert
        if (! empty($rowsToInsert)) {
            $result = $this->ingestRepository->bulkInsertReadings($rowsToInsert);
            $duplicates = $result['duplicates'];
            $accepted = $result['accepted'];
        }

        // Update device health snapshot via repository
        $this->ingestRepository->updateDeviceHealth($device->id, [
            'last_seen_at' => now(),
            'last_battery_v' => $data['battery_v'],
            'last_rssi' => $data['rssi'],
            'fw_version' => $data['fw'],
        ]);

        return [
            'received' => count($readings),
            'accepted' => $accepted,
            'duplicates' => $duplicates,
            'rejected' => $rejected,
            'items' => $items,
        ];
    }

    public function processBatch(Device $device, array $data): array
    {
        $batch = $data['batch'];
        $fw = $data['fw'];
        $items = [];
        $accepted = 0;
        $duplicates = 0;
        $rejected = 0;

        foreach ($batch as $index => $item) {
            $payload = [
                'fw' => $fw,
                'ts' => $item['ts'],
                'seq' => $item['seq'],
                'battery_v' => $item['battery_v'],
                'rssi' => $item['rssi'],
                'readings' => $item['readings'],
            ];

            try {
                $result = $this->processSingle($device, $payload);

                $items[] = [
                    'index' => $index,
                    'status' => $result['duplicates'] > 0 ? 'duplicate' : 'accepted',
                ];

                $accepted += $result['accepted'];
                $duplicates += $result['duplicates'];
            } catch (BusinessException $e) {
                $items[] = [
                    'index' => $index,
                    'status' => 'rejected',
                    'code' => $e->getErrorCode(),
                ];
                $rejected++;
            }
        }

        return [
            'received' => count($batch),
            'accepted' => $accepted,
            'duplicates' => $duplicates,
            'rejected' => $rejected,
            'items' => $items,
        ];
    }

    public function processHeartbeat(Device $device, array $data): array
    {
        $this->ingestRepository->updateDeviceHealth($device->id, [
            'last_seen_at' => now(),
            'last_battery_v' => $data['battery_v'] ?? null,
            'last_rssi' => $data['rssi'] ?? null,
            'fw_version' => $data['fw'] ?? null,
        ]);

        return ['message' => 'Heartbeat diterima.'];
    }

    protected function checkQuality(float $value, mixed $sensorType, string $sensorCode): string
    {
        // Sentinel error for temp_air
        if ($sensorCode === 'temp_air' && $value == -999) {
            return 'SENSOR_ERROR';
        }

        // Range check
        if ($value < $sensorType->min_value || $value > $sensorType->max_value) {
            return 'OUT_OF_RANGE';
        }

        return 'OK';
    }

    protected function applyCalibration(string $sensorId, float $rawValue, Carbon $deviceTs): float
    {
        $calibration = $this->ingestRepository->findActiveCalibration($sensorId, $deviceTs);

        if (! $calibration) {
            return $rawValue;
        }

        return ($rawValue * (float) $calibration->scale) + (float) $calibration->offset;
    }

    protected function calculateRainMm(string $deviceId, string $sensorId, float $rawValue, Carbon $deviceTs): float
    {
        $prev = $this->ingestRepository->findLastRainCounterByDevice($deviceId, $sensorId, $deviceTs);

        if ($prev === null) {
            return 0;
        }

        // EC-4: counter reset detection
        $delta = ($rawValue < $prev->raw_value)
            ? $rawValue
            : ($rawValue - $prev->raw_value);

        return $delta * config('ingest.rain_mm_per_tip', 0.2);
    }
}
