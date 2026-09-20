<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalibrationRequest;
use App\Services\SensorService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalibrationController extends Controller
{
    public function __construct(
        protected SensorService $sensorService,
    ) {}

    public function index(Request $request, string $id): JsonResponse
    {
        $calibrations = $this->sensorService->getCalibrations($id);
        return ApiResponse::success($calibrations);
    }

    public function store(StoreCalibrationRequest $request, string $id): JsonResponse
    {
        $calibration = $this->sensorService->createCalibration($id, $request->validated());
        return ApiResponse::success($calibration, 201);
    }
}
