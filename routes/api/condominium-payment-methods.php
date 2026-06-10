<?php

use App\Http\Controllers\Api\Condominiums\CondominiumPaymentMethodController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/payment-methods', [CondominiumPaymentMethodController::class, 'index']);
    Route::post('condominiums/{condominium}/payment-methods', [CondominiumPaymentMethodController::class, 'store']);
});
