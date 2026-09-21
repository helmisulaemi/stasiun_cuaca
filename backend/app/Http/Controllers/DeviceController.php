<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\TransitionDeviceStatusRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Services\DeviceService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceService $deviceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'location_id', 'q', 'per_page']);
        $devices = $this->deviceService->getPaginated($filters);

        return ApiResponse::success($devices);
    }

    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->deviceService->createDevice($data);

        return ApiResponse::success([
            'device' => $result['device'],
            'secret' => $result['secret'],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $device = $this->deviceService->getDeviceById($id);

        return ApiResponse::success($device);
    }

    public function update(UpdateDeviceRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $device = $this->deviceService->updateDevice($data, $id);

        return ApiResponse::success($device);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deviceService->deleteDevice($id);

        return ApiResponse::success(null, 204);
    }

    public function transitionStatus(TransitionDeviceStatusRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $userId = $request->user()?->id;

        $device = $this->deviceService->transitionStatus(
            $id, $data['status'], $data['reason'] ?? null, $userId
        );

        return ApiResponse::success($device);
    }

    public function health(string $id): JsonResponse
    {
        $health = $this->deviceService->getHealth($id);

        return ApiResponse::success($health);
    }

    public function rotateCredentials(string $id): JsonResponse
    {
        $result = $this->deviceService->rotateCredentials($id);

        return ApiResponse::success($result);
    }

    public function sensors(string $id): JsonResponse
    {
        $sensors = $this->deviceService->getInstalledSensors($id);

        return ApiResponse::success($sensors);
    }
}
