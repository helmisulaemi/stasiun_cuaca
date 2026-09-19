<?php

namespace App\Repositories;

use App\Interfaces\DeviceRepositoryInterface;
use App\Models\Device;

class DeviceRepository implements DeviceRepositoryInterface
{
    public function getAll()
    {
        return Device::with('location')->get();
    }

    public function getById($id)
    {
        return Device::with('location')->findOrFail($id);
    }

    public function store(array $data)
    {
        return Device::create($data);
    }

    public function update(array $data, $id)
    {
        $device = Device::findOrFail($id);
        $device->update($data);

        return $device;
    }

    public function delete($id)
    {
        $device = Device::findOrFail($id);
        $device->delete();

        return $device;
    }
}
