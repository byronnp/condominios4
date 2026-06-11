<?php

use App\Http\Controllers\Api\Billing\AccountOpeningBalanceController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/account-opening-balances', [AccountOpeningBalanceController::class, 'index']);
    Route::post('condominiums/{condominium}/account-opening-balances', [AccountOpeningBalanceController::class, 'store']);
});
