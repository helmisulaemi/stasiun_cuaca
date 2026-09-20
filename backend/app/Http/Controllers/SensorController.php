<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSensorRequest;
use App\Http\Requests\UpdateSensorRequest;
use App\Services\SensorService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    public function __construct(
        protected SensorService $sensorService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['sensor_type_id', 'q', 'per_page']);
        $sensors = $this->sensorService->getPaginatedSensors($filters);
        return ApiResponse::success($sensors);
    }

    public function show(string $id): JsonResponse
    {
        $sensor = $this->sensorService->getSensorById($id);
        return ApiResponse::success($sensor);
    }

    public function store(StoreSensorRequest $request): JsonResponse
    {
        $sensor = $this->sensorService->createSensor($request->validated());
        return ApiResponse::success($sensor, 201);
    }

    public function update(UpdateSensorRequest $request, string $id): JsonResponse
    {
        $sensor = $this->sensorService->updateSensor($request->validated(), $id);
        return ApiResponse::success($sensor);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->sensorService->deleteSensor($id);
        return ApiResponse::success(null, 204);
    }
}
