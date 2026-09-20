<?php

use App\Exceptions\Handler;
use App\Support\Api\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |----------------------------------------------
    | Ingestion Endpoints (Device Auth)
    |----------------------------------------------
    */
    Route::prefix('ingest')->middleware(['device.auth', 'throttle:device'])->group(function() {

        Route::prefix('telemetry')->group(function() {
            Route::post('/', function () {
                return ApiResponse::success(['message' => 'TODO: D1 — Single telemetry']);
            });
            Route::post('/batch', function () {
                return ApiResponse::success(['message' => 'TODO: D2 — Batch telemetry']);
            });
        });

        Route::post('/heartbeat', function () {
            return ApiResponse::success(['message' => 'TODO: D3 — Heartbeat']);
        });
    });

});
