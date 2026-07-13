<?php

use App\Http\Controllers\Api\Users\UserBillingProfileController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/units/{unit}/users/{user}/billing-profiles', [UserBillingProfileController::class, 'index']);
    Route::post('condominiums/{condominium}/units/{unit}/users/{user}/billing-profiles', [UserBillingProfileController::class, 'store']);
});
