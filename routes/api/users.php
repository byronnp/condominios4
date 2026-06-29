<?php

use App\Http\Controllers\Api\Users\UserController;
use App\Http\Controllers\Api\Users\ResendInvitationController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function (): void {
    Route::get('users/form-options', [UserController::class, 'formOptions']);
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::patch('users/{user}/status', [UserController::class, 'updateStatus']);
    Route::post('users/{user}/resend-invitation', ResendInvitationController::class);
});
