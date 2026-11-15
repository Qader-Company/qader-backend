<?php

use App\Modules\V1\Trash\Presentation\Http\Controllers\ClientTrashController;
use Illuminate\Support\Facades\Route;

Route::get('workspaces', [ClientTrashController::class, 'workspaces']);
Route::post('workspaces/{workspace}/restore', [ClientTrashController::class, 'restoreWorkspace'])->whereNumber('workspace');
Route::post('workspaces/{workspace}/remove', [ClientTrashController::class, 'removeWorkspace'])->whereNumber('workspace');
