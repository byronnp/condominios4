<?php

use App\Http\Controllers\Api\Billing\BankAccountMovementController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/bank-account-movements', [BankAccountMovementController::class, 'index']);
    Route::post('condominiums/{condominium}/bank-account-movements', [BankAccountMovementController::class, 'store']);
});
