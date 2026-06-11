<?php

use App\Http\Controllers\Api\Billing\PaymentOrderController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/payment-orders', [PaymentOrderController::class, 'index']);
    Route::post('condominiums/{condominium}/payment-orders', [PaymentOrderController::class, 'store']);
});
