<?php

use App\Http\Controllers\Api\PlatformAdministrators\PlatformAdministratorController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('platform-administrators', [PlatformAdministratorController::class, 'index']);
    Route::post('platform-administrators', [PlatformAdministratorController::class, 'store']);
    Route::get('platform-administrators/{user}', [PlatformAdministratorController::class, 'show']);
    Route::patch('platform-administrators/{user}', [PlatformAdministratorController::class, 'update']);
    Route::patch('platform-administrators/{user}/status', [PlatformAdministratorController::class, 'updateStatus']);
    Route::delete('platform-administrators/{user}', [PlatformAdministratorController::class, 'destroy']);
});
