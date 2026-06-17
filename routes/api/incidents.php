<?php

use App\Http\Controllers\Api\Operations\IncidentController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/incidents', [IncidentController::class, 'index']);
    Route::post('condominiums/{condominium}/incidents', [IncidentController::class, 'store']);
});
