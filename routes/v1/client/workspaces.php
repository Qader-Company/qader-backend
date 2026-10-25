<?php

use App\Modules\V1\Workspaces\Presentation\Http\Controllers\WorkspaceController;
use App\Modules\V1\Workspaces\Presentation\Http\Controllers\WorkspaceMemberController;
use App\Modules\V1\Workspaces\Presentation\Http\Controllers\WorkspaceOptionController;
use Illuminate\Support\Facades\Route;

Route::get('options', WorkspaceOptionController::class);
Route::get('/', [WorkspaceController::class, 'index']);
Route::post('/', [WorkspaceController::class, 'store']);
Route::get('{workspace}', [WorkspaceController::class, 'show']);
Route::patch('{workspace}', [WorkspaceController::class, 'update']);
Route::get('{workspace}/dashboard', [WorkspaceController::class, 'dashboard']);
Route::get('{workspace}/members', [WorkspaceMemberController::class, 'index']);
Route::post('{workspace}/members', [WorkspaceMemberController::class, 'store']);
Route::get('{workspace}/members/{member}', [WorkspaceMemberController::class, 'show']);
Route::patch('{workspace}/members/{member}', [WorkspaceMemberController::class, 'update']);
Route::delete('{workspace}/members/{member}', [WorkspaceMemberController::class, 'destroy']);
