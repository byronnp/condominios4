<?php

use App\Http\Controllers\Api\Billing\MonthlyFeeController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/monthly-fees', [MonthlyFeeController::class, 'index']);
    Route::post('condominiums/{condominium}/monthly-fees/generate', [MonthlyFeeController::class, 'generate']);
});
