<?php

use App\Http\Controllers\Api\Roles\RoleController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/roles', [RoleController::class, 'index']);
    Route::post('condominiums/{condominium}/roles', [RoleController::class, 'store']);
    Route::get('condominiums/{condominium}/roles/{role}', [RoleController::class, 'show']);
    Route::put('condominiums/{condominium}/roles/{role}', [RoleController::class, 'update']);
    Route::delete('condominiums/{condominium}/roles/{role}', [RoleController::class, 'destroy']);
    Route::put('condominiums/{condominium}/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
});
