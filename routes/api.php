<?php

use App\Http\Controllers\Api\ApiClientController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.key', 'throttle:60,1'])->group(function () {
    Route::get('/clients', [ApiClientController::class, 'index']);
    Route::post('/clients', [ApiClientController::class, 'store']);
    Route::patch('/clients/{apiClient}', [ApiClientController::class, 'update']);
    Route::post('/clients/{apiClient}/regenerate-key', [ApiClientController::class, 'regenerate']);

    Route::get('/messages', [MessageController::class, 'all']);
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::post('/devices', [DeviceController::class, 'store']);
    Route::middleware('device.owner')->group(function () {
        Route::get('/devices/{device}', [DeviceController::class, 'show']);
        Route::post('/devices/{device}/connect', [DeviceController::class, 'connect']);
        Route::get('/devices/{device}/qr', [DeviceController::class, 'qr']);
        Route::post('/devices/{device}/disconnect', [DeviceController::class, 'disconnect']);
        Route::post('/devices/{device}/logout', [DeviceController::class, 'logout']);
        Route::delete('/devices/{device}', [DeviceController::class, 'destroy']);

        Route::get('/devices/{device}/messages', [MessageController::class, 'index']);
        Route::post('/devices/{device}/messages', [MessageController::class, 'store']);
    });
});
