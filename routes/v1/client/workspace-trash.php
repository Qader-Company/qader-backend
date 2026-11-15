<?php

use App\Modules\V1\Trash\Presentation\Http\Controllers\ClientTrashController;
use Illuminate\Support\Facades\Route;

Route::prefix('{workspace}/trash')->whereNumber('workspace')->controller(ClientTrashController::class)->group(function (): void {
    Route::get('team', 'team')->defaults('trashResource', 'team');
    Route::post('team/{member}/restore', 'restoreMember')->whereNumber('member');
    Route::post('team/{member}/remove', 'removeMember')->whereNumber('member');
    Route::get('roles', 'roles')->defaults('trashResource', 'workspace-roles');
    Route::post('roles/{role}/restore', 'restoreRole')->whereNumber('role');
    Route::post('roles/{role}/remove', 'removeRole')->whereNumber('role');
});
