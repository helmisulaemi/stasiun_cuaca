<?php

namespace App\Services;

use App\Interfaces\DeviceRepositoryInterface;

class DeviceService
{
    protected DeviceRepositoryInterface $deviceRepository;

    public function __construct(DeviceRepositoryInterface $deviceRepository)
    {
        $this->deviceRepository = $deviceRepository;
    }

    public function getAllDevices()
    {
        return $this->deviceRepository->getAll();
    }

    public function getDeviceById($id)
    {
        return $this->deviceRepository->getById($id);
    }

    public function createDevice(array $data)
    {
        return $this->deviceRepository->store($data);
    }

    public function updateDevice(array $data, $id)
    {
        return $this->deviceRepository->update($data, $id);
    }

    public function deleteDevice($id)
    {
        return $this->deviceRepository->delete($id);
    }
}
