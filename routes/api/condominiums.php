<?php

use App\Http\Controllers\Api\Condominiums\CondominiumController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums', [CondominiumController::class, 'index']);
    Route::post('condominiums', [CondominiumController::class, 'store']);
    Route::put('condominiums/{condominium}', [CondominiumController::class, 'update']);
    Route::delete('condominiums/{condominium}', [CondominiumController::class, 'destroy']);
    Route::get('condominiums/{condominium}', [CondominiumController::class, 'show']);
});
