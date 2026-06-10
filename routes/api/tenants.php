<?php

use App\Http\Controllers\Api\TenantController;
use App\Http\Middleware\AuthenticateWithApiToken;
use App\Http\Middleware\EnsureTenantExists;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::middleware([AuthenticateWithApiToken::class, EnsureTenantExists::class])->group(function () {
    Route::middleware([EnsureUserHasRole::class . ':admin'])->group(function () {
        Route::get('tenants', [TenantController::class, 'index']);
        Route::post('tenants', [TenantController::class, 'store']);
        Route::get('roles', [TenantController::class, 'roles']);
    });
});
