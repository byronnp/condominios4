<?php

use App\Http\Controllers\Api\Administrators\AdministratorController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/administrators', [AdministratorController::class, 'indexByCondominium']);
    Route::post('condominiums/{condominium}/administrators', [AdministratorController::class, 'storeInCondominium']);
    Route::get('condominiums/{condominium}/administrators/{user}', [AdministratorController::class, 'showInCondominium']);
    Route::put('condominiums/{condominium}/administrators/{user}', [AdministratorController::class, 'updateInCondominium']);
    Route::patch('condominiums/{condominium}/administrators/{user}/status', [AdministratorController::class, 'updateStatusInCondominium']);
    Route::delete('condominiums/{condominium}/administrators/{user}', [AdministratorController::class, 'destroyInCondominium']);
});
