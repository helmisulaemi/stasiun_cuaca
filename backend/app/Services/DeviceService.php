<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Interfaces\DeviceRepositoryInterface;
use App\Models\Device;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class DeviceService
{
    public function __construct(
        protected DeviceRepositoryInterface $deviceRepository,
    ) {}

    public function getAllDevices()
    {
        return $this->deviceRepository->getAll();
    }

    public function getDeviceById($id): Device
    {
        return $this->deviceRepository->getById($id);
    }

    public function getPaginated(array $filters): LengthAwarePaginator
    {
        return $this->deviceRepository->getPaginated($filters);
    }

    public function createDevice(array $data): array
    {
        $secret = $this->deviceRepository->generateSecret();

        $device = $this->deviceRepository->store([
            'id' => Str::uuid()->toString(),
            'name' => $data['name'],
            'location_id' => $data['location_id'],
            'status' => 'provisioned',
            'secret_hash' => $secret['hash'],
        ]);

        $this->deviceRepository->recordStatusTransition(
            $device->id, null, 'provisioned', 'Device registered', null
        );

        return [
            'device' => $device,
            'secret' => $secret['plain'],
        ];
    }

    public function updateDevice(array $data, $id): Device
    {
        return $this->deviceRepository->update($data, $id);
    }

    public function deleteDevice($id): void
    {
        $this->deviceRepository->delete($id);
    }

    public function transitionStatus(string $id, string $newStatus, ?string $reason, ?string $userId): Device
    {
        $device = $this->deviceRepository->getById($id);
        $currentStatus = $device->status;

        $allowed = config('device.transitions.' . $currentStatus, []);
        if (! in_array($newStatus, $allowed)) {
            throw new BusinessException(409, 'INVALID_TRANSITION', "Transisi dari '$currentStatus' ke '$newStatus' tidak diizinkan.");
        }

        $device = $this->deviceRepository->update(['status' => $newStatus], $id);

        $this->deviceRepository->recordStatusTransition(
            $id, $currentStatus, $newStatus, $reason, $userId
        );

        return $device;
    }

    public function rotateCredentials(string $id): array
    {
        $device = $this->deviceRepository->getById($id);
        $secret = $this->deviceRepository->generateSecret();

        $this->deviceRepository->update(['secret_hash' => $secret['hash']], $id);

        return [
            'device_id' => $device->id,
            'name' => $device->name,
            'secret' => $secret['plain'],
        ];
    }

    public function getHealth(string $id): array
    {
        $health = $this->deviceRepository->getHealthData($id);
        if (! $health) {
            throw new BusinessException(404, 'NOT_FOUND', 'Device tidak ditemukan.');
        }

        return $health;
    }
}
