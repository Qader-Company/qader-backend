<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Languages\Presentation\Http\Controllers\AdminLanguageController;
use App\Modules\V1\Trash\Presentation\Http\Controllers\AdminTrashDeletionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminLanguageController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->middleware(AdminPermissionEnum::LANGUAGES_VIEW->middleware());

        Route::post('/', 'store')
            ->middleware(AdminPermissionEnum::LANGUAGES_MANAGE->middleware());

        Route::patch('{record}', 'update')
            ->middleware(AdminPermissionEnum::LANGUAGES_MANAGE->middleware());
        Route::delete('{record}', AdminTrashDeletionController::class)->whereNumber('record')->defaults('trashResource', 'languages')->middleware(AdminPermissionEnum::LANGUAGES_MANAGE->middleware());
    });
