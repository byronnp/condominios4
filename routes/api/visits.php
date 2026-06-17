<?php

use App\Http\Controllers\Api\Operations\VisitController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/visits', [VisitController::class, 'index']);
    Route::post('condominiums/{condominium}/visits', [VisitController::class, 'store']);
    Route::patch('condominiums/{condominium}/visits/{visit}/authorize', [VisitController::class, 'authorizeVisit']);
    Route::post('condominiums/{condominium}/visits/{visit}/entry', [VisitController::class, 'entry']);
    Route::post('condominiums/{condominium}/visits/{visit}/exit', [VisitController::class, 'exit']);
});
