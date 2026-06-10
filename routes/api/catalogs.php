<?php

use App\Http\Controllers\Api\Catalogs\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('catalogs', [CatalogController::class, 'index']);
Route::get('catalogs/{catalog}', [CatalogController::class, 'show']);
Route::get('catalogs/{catalog}/items', [CatalogController::class, 'items']);
