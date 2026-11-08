<?php

use App\Modules\V1\AccessControl\Presentation\Http\Controllers\WorkspaceAccessControlController;
use Illuminate\Support\Facades\Route;

Route::prefix('workspaces/{workspace}')->controller(WorkspaceAccessControlController::class)->group(function (): void {
    Route::get('permissions', 'permissions');
    Route::get('roles', 'index');
    Route::post('roles', 'store');
    Route::get('roles/{role}', 'show');
    Route::patch('roles/{role}', 'update');
    Route::patch('roles/{role}/status', 'status');
    Route::delete('roles/{role}', 'destroy');
});
