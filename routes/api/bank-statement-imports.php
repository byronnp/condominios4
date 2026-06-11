<?php

use App\Http\Controllers\Api\Billing\BankStatementImportController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateJwt::class)->group(function () {
    Route::get('condominiums/{condominium}/bank-statement-imports', [BankStatementImportController::class, 'index']);
    Route::post('condominiums/{condominium}/bank-statement-imports', [BankStatementImportController::class, 'store']);
});
