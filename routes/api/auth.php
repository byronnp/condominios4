<?php

use App\Http\Controllers\Api\Auth\JwtAuthController;
use App\Http\Controllers\Api\Menus\MenuController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [JwtAuthController::class, 'register']);
    Route::post('login', [JwtAuthController::class, 'login']);
    Route::post('refresh', [JwtAuthController::class, 'refresh']);

    Route::middleware(AuthenticateJwt::class)->group(function () {
        Route::post('logout', [JwtAuthController::class, 'logout']);
        Route::post('logout-all', [JwtAuthController::class, 'logoutAll']);
        Route::get('me', [JwtAuthController::class, 'me']);
        Route::get('menu', [MenuController::class, 'current']);
        Route::get('sessions', [JwtAuthController::class, 'sessions']);
        Route::delete('sessions/{session}', [JwtAuthController::class, 'revokeSession']);
    });
});

Route::post('register', [JwtAuthController::class, 'register']);
Route::post('login', [JwtAuthController::class, 'login']);

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::post('logout', [JwtAuthController::class, 'logout']);
    Route::get('user', [JwtAuthController::class, 'me']);
});
