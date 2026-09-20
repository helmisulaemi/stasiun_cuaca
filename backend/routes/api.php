<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\IngestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |----------------------------------------------
    | Auth Endpoints (Public)
    |----------------------------------------------
    */
    Route::post('/auth/login', [LoginController::class, 'login']);

    /*
    |----------------------------------------------
    | Ingestion Endpoints (Device Auth)
    |----------------------------------------------
    */
    Route::prefix('ingest')->middleware(['device.auth', 'throttle:device'])->group(function() {

        Route::prefix('telemetry')->group(function() {
            Route::post('/', [IngestController::class, 'store']);
            Route::post('/batch', [IngestController::class, 'storeBatch']);
        });

        Route::post('/heartbeat', [IngestController::class, 'heartbeat']);
    });

    /*
    |----------------------------------------------
    | Device Management Endpoints (User Auth)
    |----------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        Route::apiResource('devices', DeviceController::class)
            ->except(['create', 'edit']);

        Route::post('/devices/{id}/status', [DeviceController::class, 'transitionStatus']);
        Route::get('/devices/{id}/health', [DeviceController::class, 'health']);
        Route::post('/devices/{id}/credentials/rotate', [DeviceController::class, 'rotateCredentials']);
    });

});
