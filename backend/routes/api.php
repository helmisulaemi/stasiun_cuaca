<?php

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
    | Ingestion Endpoints (Device Auth)
    |----------------------------------------------
    */
    Route::prefix('ingest')->middleware(['device.auth', 'throttle:device'])->group(function() {

        Route::prefix('telemetry')->group(function() {
            Route::post('/', [IngestController::class, 'store']);
            Route::post('/batch', function () {
                return response()->json(['message' => 'TODO: D2 — Batch telemetry']);
            });
        });

        Route::post('/heartbeat', [IngestController::class, 'heartbeat']);
    });

});
