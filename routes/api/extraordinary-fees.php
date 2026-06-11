<?php

use App\Http\Controllers\Api\Billing\ExtraordinaryFeeController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/extraordinary-fees', [ExtraordinaryFeeController::class, 'index']);
    Route::post('condominiums/{condominium}/extraordinary-fees', [ExtraordinaryFeeController::class, 'store']);
});
