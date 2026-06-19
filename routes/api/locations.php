<?php

use App\Http\Controllers\Api\Locations\LocationController;
use Illuminate\Support\Facades\Route;

Route::get('countries', [LocationController::class, 'countries']);
Route::get('countries/{country}', [LocationController::class, 'country']);
Route::get('countries/{country}/provinces', [LocationController::class, 'provinces']);
Route::get('provinces/{province}/cities', [LocationController::class, 'cities']);
