<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Interfaces\SensorRepositoryInterface;
use App\Models\Sensor;
use App\Models\SensorCalibration;
use App\Models\SensorInstallation;
use App\Models\SensorType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SensorService
{
    public function __construct(protected SensorRepositoryInterface $sensorRepository) {}

    public function getPaginatedSensors(array $filters): LengthAwarePaginator
    {
        return $this->sensorRepository->getPaginated($filters);
    }

    public function getSensorById(string $id): Sensor
    {
        $sensor = $this->sensorRepository->getById($id);
        if (! $sensor) {
            throw new BusinessException(404, 'NOT_FOUND', 'Sensor tidak ditemukan.');
        }
        return $sensor;
    }

    public function createSensor(array $data): Sensor
    {
        return $this->sensorRepository->store([
            'id' => Str::uuid()->toString(),
            'sensor_type_id' => $data['sensor_type_id'],
            'serial_number' => $data['serial_number'],
            'model' => $data['model'] ?? null,
        ]);
    }

    public function updateSensor(array $data, string $id): Sensor
    {
        $this->getSensorById($id);
        return $this->sensorRepository->update($data, $id);
    }

    public function deleteSensor(string $id): void
    {
        $sensor = $this->getSensorById($id);

        $activeInstallation = SensorInstallation::where('sensor_id', $id)
            ->whereNull('removed_at')
            ->first();

        if ($activeInstallation) {
            throw new BusinessException(
                409,
                'CONFLICT',
                'Sensor masih terpasang di device. Lepas terlebih dahulu sebelum menghapus.'
            );
        }

        $this->sensorRepository->delete($id);
    }

    public function getPaginatedSensorTypes(): LengthAwarePaginator
    {
        return SensorType::orderBy('name')->paginate(15);
    }

    public function createSensorType(array $data): SensorType
    {
        return SensorType::create([
            'id' => Str::uuid()->toString(),
            'name' => $data['name'],
            'unit' => $data['unit'],
            'min_value' => $data['min_value'],
            'max_value' => $data['max_value'],
            'precision' => $data['precision'],
        ]);
    }

    public function installSensor(string $deviceId, string $sensorId): SensorInstallation
    {
        $existingActive = SensorInstallation::where('sensor_id', $sensorId)
            ->whereNull('removed_at')
            ->first();

        if ($existingActive) {
            if ($existingActive->device_id === $deviceId) {
                throw new BusinessException(409, 'CONFLICT', 'Sensor sudah terpasang di device ini.');
            }
            throw new BusinessException(409, 'CONFLICT', 'Sensor masih terpasang di device lain. Lepas terlebih dahulu.');
        }

        return SensorInstallation::create([
            'id' => Str::uuid()->toString(),
            'device_id' => $deviceId,
            'sensor_id' => $sensorId,
            'installed_at' => now(),
        ]);
    }

    public function removeSensor(string $deviceId, string $sensorId): SensorInstallation
    {
        $installation = SensorInstallation::where('device_id', $deviceId)
            ->where('sensor_id', $sensorId)
            ->whereNull('removed_at')
            ->first();

        if (! $installation) {
            throw new BusinessException(404, 'NOT_FOUND', 'Instalasi sensor tidak ditemukan.');
        }

        $installation->update(['removed_at' => now()]);

        return $installation;
    }

    public function getCalibrations(string $sensorId): LengthAwarePaginator
    {
        $this->getSensorById($sensorId);

        return SensorCalibration::where('sensor_id', $sensorId)
            ->orderBy('effective_from', 'desc')
            ->paginate(15);
    }

    public function createCalibration(string $sensorId, array $data): SensorCalibration
    {
        $this->getSensorById($sensorId);

        return SensorCalibration::create([
            'id' => Str::uuid()->toString(),
            'sensor_id' => $sensorId,
            'offset' => $data['offset'],
            'scale' => $data['scale'],
            'effective_from' => $data['effective_from'],
        ]);
    }
}
