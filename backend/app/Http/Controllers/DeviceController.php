<?php

namespace App\Http\Controllers;

use App\Services\DeviceService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    protected DeviceService $deviceService;

    public function __construct(DeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    public function index(): JsonResponse
    {
        $devices = $this->deviceService->getAllDevices();

        return ApiResponse::success($devices);
    }

    public function show(string $id): JsonResponse
    {
        $device = $this->deviceService->getDeviceById($id);

        return ApiResponse::success($device);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location_id' => 'required|uuid|exists:locations,id',
            'status' => 'required|string|in:provisioned,active,maintenance,decommissioned',
            'secret_hash' => 'required|string|max:255',
        ]);

        $device = $this->deviceService->createDevice($data);

        return ApiResponse::success($device, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location_id' => 'sometimes|uuid|exists:locations,id',
            'status' => 'sometimes|string|in:provisioned,active,maintenance,decommissioned',
            'secret_hash' => 'sometimes|string|max:255',
        ]);

        $device = $this->deviceService->updateDevice($data, $id);

        return ApiResponse::success($device);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deviceService->deleteDevice($id);

        return ApiResponse::success(null, 204);
    }
}
