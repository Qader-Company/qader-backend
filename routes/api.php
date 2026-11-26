<?php

use App\Http\Controllers\MaintenanceCommandController;
use Illuminate\Support\Facades\Route;

if (app()->environment(['local', 'testing'])) {
    Route::post('/maintenance/{command}', MaintenanceCommandController::class)
        ->middleware('api.key')
        ->whereIn('command', [
            'migrate',
            'migrate-fresh',
            'rollback',
            'seed',
            'optimize-clear',
            'create-api-key',
            'queue-work',
            'queue-restart',
            'storage-link',
            'fcm-test',
        ]);
}
