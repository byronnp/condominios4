<?php

use App\Http\Controllers\Api\Units\UnitController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/units', [UnitController::class, 'index']);
    Route::post('condominiums/{condominium}/units', [UnitController::class, 'store']);
    Route::get('condominiums/{condominium}/units/{unit}', [UnitController::class, 'show']);
    Route::patch('condominiums/{condominium}/units/{unit}', [UnitController::class, 'update']);
});
