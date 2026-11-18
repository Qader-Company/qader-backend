<?php

use App\Modules\V1\AdminDashboard\Presentation\Http\Controllers\AdminDashboardController;
use App\Modules\V1\AdminDashboard\Presentation\Http\Controllers\AdminDashboardOptionsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])->group(function (): void {
    Route::get('options', AdminDashboardOptionsController::class);
    Route::get('/', AdminDashboardController::class);
});
