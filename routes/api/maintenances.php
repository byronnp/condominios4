<?php

use App\Http\Controllers\Api\Operations\MaintenanceController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/maintenances', [MaintenanceController::class, 'index']);
    Route::post('condominiums/{condominium}/maintenances', [MaintenanceController::class, 'store']);
    Route::post('condominiums/{condominium}/maintenances/{maintenance}/tasks', [MaintenanceController::class, 'storeTask']);
    Route::patch('condominiums/{condominium}/maintenances/{maintenance}/tasks/{task}/complete', [MaintenanceController::class, 'completeTask']);
});
