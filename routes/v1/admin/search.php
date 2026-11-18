<?php

use App\Modules\V1\AdminDashboard\Presentation\Http\Controllers\AdminGlobalSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->get('/', AdminGlobalSearchController::class);
