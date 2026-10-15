<?php

use App\Http\Controllers\MaintenanceCommandController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/maintenance/{command}', MaintenanceCommandController::class)
    ->whereIn('command', ['migrate', 'seed', 'optimize-clear']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
