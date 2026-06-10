<?php

use App\Http\Controllers\Api\Units\UnitUserController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/units/{unit}/users', [UnitUserController::class, 'index']);
    Route::post('condominiums/{condominium}/units/{unit}/users', [UnitUserController::class, 'store']);
    Route::patch('condominiums/{condominium}/units/{unit}/users/{user}/deactivate', [UnitUserController::class, 'deactivate']);
    Route::patch('condominiums/{condominium}/units/{unit}/billing-responsible', [UnitUserController::class, 'billingResponsible']);
});
