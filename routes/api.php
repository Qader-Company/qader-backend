<?php

use App\Http\Controllers\MaintenanceCommandController;
use Illuminate\Support\Facades\Route;

Route::post('/maintenance/{command}', MaintenanceCommandController::class)
    ->whereIn('command', [
        'migrate',
        'migrate-fresh',
        'rollback',
        'seed',
        'optimize-clear',
        'create-api-key',
        'queue-work',
    ]);
