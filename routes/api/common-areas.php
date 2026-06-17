<?php

use App\Http\Controllers\Api\Operations\CommonAreaController;
use App\Http\Controllers\Api\Operations\CommonAreaReservationController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/common-areas', [CommonAreaController::class, 'index']);
    Route::post('condominiums/{condominium}/common-areas', [CommonAreaController::class, 'store']);
    Route::get('condominiums/{condominium}/common-area-reservations', [CommonAreaReservationController::class, 'index']);
    Route::post('condominiums/{condominium}/common-area-reservations', [CommonAreaReservationController::class, 'store']);
    Route::patch('condominiums/{condominium}/common-area-reservations/{reservation}/approve', [CommonAreaReservationController::class, 'approve']);
    Route::patch('condominiums/{condominium}/common-area-reservations/{reservation}/cancel', [CommonAreaReservationController::class, 'cancel']);
});
