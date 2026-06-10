<?php

use App\Http\Controllers\Api\Units\BlockController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/blocks', [BlockController::class, 'index']);
    Route::post('condominiums/{condominium}/blocks', [BlockController::class, 'store']);
});
