<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\JobTitles\Presentation\Http\Controllers\AdminJobTitleController;
use App\Modules\V1\Trash\Presentation\Http\Controllers\AdminTrashDeletionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminJobTitleController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->middleware(AdminPermissionEnum::JOB_TITLES_VIEW->middleware());

        Route::post('/', 'store')
            ->middleware(AdminPermissionEnum::JOB_TITLES_MANAGE->middleware());

        Route::patch('{record}', 'update')
            ->middleware(AdminPermissionEnum::JOB_TITLES_MANAGE->middleware());
        Route::delete('{record}', AdminTrashDeletionController::class)->whereNumber('record')->defaults('trashResource', 'job-titles')->middleware(AdminPermissionEnum::JOB_TITLES_MANAGE->middleware());
    });
