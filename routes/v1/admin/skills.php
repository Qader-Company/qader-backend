<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Skills\Presentation\Http\Controllers\AdminSkillController;
use App\Modules\V1\Trash\Presentation\Http\Controllers\AdminTrashDeletionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminSkillController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->middleware(AdminPermissionEnum::SKILLS_VIEW->middleware());

        Route::post('/', 'store')
            ->middleware(AdminPermissionEnum::SKILLS_MANAGE->middleware());

        Route::patch('{record}', 'update')
            ->middleware(AdminPermissionEnum::SKILLS_MANAGE->middleware());
        Route::delete('{record}', AdminTrashDeletionController::class)->whereNumber('record')->defaults('trashResource', 'skills')->middleware(AdminPermissionEnum::SKILLS_MANAGE->middleware());
    });
