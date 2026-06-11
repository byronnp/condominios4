<?php

use App\Http\Controllers\Api\Billing\PaymentController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/payments', [PaymentController::class, 'index']);
    Route::post('condominiums/{condominium}/payments', [PaymentController::class, 'store']);
});
