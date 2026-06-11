<?php

use App\Http\Controllers\Api\Billing\ExpenseCategoryController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/expense-categories', [ExpenseCategoryController::class, 'index']);
    Route::post('condominiums/{condominium}/expense-categories', [ExpenseCategoryController::class, 'store']);
});
