<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInstallationRequest;
use App\Services\SensorService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class InstallationController extends Controller
{
    public function __construct(
        protected SensorService $sensorService,
    ) {}

    public function store(StoreInstallationRequest $request, string $id): JsonResponse
    {
        $installation = $this->sensorService->installSensor($id, $request->validated()['sensor_id']);
        return ApiResponse::success($installation, 201);
    }

    public function destroy(string $id, string $sensorId): JsonResponse
    {
        $this->sensorService->removeSensor($id, $sensorId);
        return ApiResponse::success(null, 204);
    }
}
