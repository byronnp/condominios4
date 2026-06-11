<?php

use App\Http\Controllers\Api\Billing\BankReconciliationController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/bank-reconciliations', [BankReconciliationController::class, 'index']);
    Route::post('condominiums/{condominium}/bank-reconciliations', [BankReconciliationController::class, 'store']);
});
