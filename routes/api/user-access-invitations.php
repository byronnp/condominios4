<?php

use App\Http\Controllers\Api\Users\UserAccessInvitationController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::post('access-invitations/{token}/accept', [UserAccessInvitationController::class, 'accept']);

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::post('condominiums/{condominium}/units/{unit}/users/{user}/access-invitations', [UserAccessInvitationController::class, 'store']);
    Route::patch('condominiums/{condominium}/units/{unit}/access-invitations/{invitation}/cancel', [UserAccessInvitationController::class, 'cancel']);
});
