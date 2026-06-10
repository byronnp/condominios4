<?php

use App\Http\Controllers\Api\System\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('health', HealthController::class);
