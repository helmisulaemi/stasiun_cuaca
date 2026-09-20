<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSensorTypeRequest;
use App\Services\SensorService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class SensorTypeController extends Controller
{
    public function __construct(
        protected SensorService $sensorService,
    ) {}

    public function index(): JsonResponse
    {
        $types = $this->sensorService->getPaginatedSensorTypes();
        return ApiResponse::success($types);
    }

    public function store(StoreSensorTypeRequest $request): JsonResponse
    {
        $type = $this->sensorService->createSensorType($request->validated());
        return ApiResponse::success($type, 201);
    }
}
