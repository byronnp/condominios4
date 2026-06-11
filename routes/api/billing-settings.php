<?php

use App\Http\Controllers\Api\Billing\BillingSettingController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/billing-settings', [BillingSettingController::class, 'show']);
    Route::post('condominiums/{condominium}/billing-settings', [BillingSettingController::class, 'store']);
});
