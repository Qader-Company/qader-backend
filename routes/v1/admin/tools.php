<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Tools\Presentation\Http\Controllers\AdminToolController;
use App\Modules\V1\Trash\Presentation\Http\Controllers\AdminTrashDeletionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminToolController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->middleware(AdminPermissionEnum::TOOLS_VIEW->middleware());

        Route::post('/', 'store')
            ->middleware(AdminPermissionEnum::TOOLS_MANAGE->middleware());

        Route::patch('{record}', 'update')
            ->middleware(AdminPermissionEnum::TOOLS_MANAGE->middleware());
        Route::delete('{record}', AdminTrashDeletionController::class)->whereNumber('record')->defaults('trashResource', 'tools')->middleware(AdminPermissionEnum::TOOLS_MANAGE->middleware());
    });
