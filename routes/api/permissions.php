<?php

use App\Http\Controllers\Api\Permissions\PermissionController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::post('permissions', [PermissionController::class, 'store']);
    Route::put('permissions/{permission}', [PermissionController::class, 'update']);
    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy']);
});
