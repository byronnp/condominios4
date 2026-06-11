<?php

use App\Http\Controllers\Api\Billing\ExpenseController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/expenses', [ExpenseController::class, 'index']);
    Route::post('condominiums/{condominium}/expenses', [ExpenseController::class, 'store']);
});
