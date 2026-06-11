<?php

use App\Http\Controllers\Api\Billing\TreasuryHandoverController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/treasury-handovers', [TreasuryHandoverController::class, 'index']);
    Route::post('condominiums/{condominium}/treasury-handovers/calculate', [TreasuryHandoverController::class, 'calculate']);
    Route::post('condominiums/{condominium}/treasury-handovers', [TreasuryHandoverController::class, 'store']);
});
