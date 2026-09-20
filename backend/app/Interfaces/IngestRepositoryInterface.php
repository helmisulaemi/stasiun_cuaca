<?php

namespace App\Interfaces;

use App\Models\SensorCalibration;
use App\Models\SensorInstallation;
use App\Models\SensorType;
use Carbon\Carbon;

interface IngestRepositoryInterface
{
    public function bulkInsertReadings(array $readings): array;

    public function findSensorTypeByName(string $name): ?SensorType;

    public function findActiveInstallationByDeviceAndType(string $deviceId, string $sensorTypeId): ?SensorInstallation;

    public function findActiveCalibration(string $sensorId, Carbon $ts): ?SensorCalibration;

    public function findLastRainCounterByDevice(string $deviceId, string $sensorId, Carbon $currentTs): ?object;

    public function updateDeviceHealth(string $deviceId, array $data): void;
}
