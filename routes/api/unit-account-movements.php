<?php

use App\Http\Controllers\Api\Billing\UnitAccountMovementController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/units/{unit}/account-movements', [UnitAccountMovementController::class, 'index']);
});
