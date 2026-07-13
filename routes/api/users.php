<?php

use App\Http\Controllers\Api\Users\UserController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function (): void {
    Route::get('condominiums/{condominium}/users/form-options', [UserController::class, 'formOptions']);
    Route::get('condominiums/{condominium}/users', [UserController::class, 'indexByCondominium']);
    Route::post('condominiums/{condominium}/users', [UserController::class, 'storeInCondominium']);
    Route::get('condominiums/{condominium}/users/{user}', [UserController::class, 'showInCondominium']);
    Route::put('condominiums/{condominium}/users/{user}', [UserController::class, 'updateInCondominium']);
    Route::patch('condominiums/{condominium}/users/{user}/status', [UserController::class, 'updateStatusInCondominium']);
});
