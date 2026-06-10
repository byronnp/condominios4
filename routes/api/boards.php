<?php

use App\Http\Controllers\Api\Boards\BoardController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/boards', [BoardController::class, 'index']);
    Route::post('condominiums/{condominium}/boards', [BoardController::class, 'store']);
});
