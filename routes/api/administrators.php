<?php

use App\Http\Controllers\Api\Administrators\AdministratorController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('administrators', [AdministratorController::class, 'index']);
    Route::post('administrators', [AdministratorController::class, 'store']);
    Route::get('administrators/{administrator}', [AdministratorController::class, 'show']);
    Route::put('administrators/{administrator}', [AdministratorController::class, 'update']);
    Route::patch('administrators/{administrator}/status', [AdministratorController::class, 'updateStatus']);
    Route::post('administrators/{administrator}/condominiums', [AdministratorController::class, 'assignCondominium']);
    Route::delete('administrators/{administrator}/condominiums/{condominium}', [AdministratorController::class, 'removeCondominium']);
    Route::delete('administrators/{administrator}', [AdministratorController::class, 'destroy']);
});
