<?php

use App\Http\Controllers\Api\Operations\VisitorController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/visitors', [VisitorController::class, 'index']);
    Route::post('condominiums/{condominium}/visitors', [VisitorController::class, 'store']);
});
