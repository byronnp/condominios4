<?php

use App\Http\Controllers\Api\Billing\BillingConceptController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('billing-concepts', [BillingConceptController::class, 'index']);
    Route::post('billing-concepts', [BillingConceptController::class, 'store']);
});
