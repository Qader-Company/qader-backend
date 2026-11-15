<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Countries\Presentation\Http\Controllers\AdminCountryController;
use App\Modules\V1\Trash\Presentation\Http\Controllers\AdminTrashDeletionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminCountryController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->middleware(AdminPermissionEnum::COUNTRIES_VIEW->middleware());

        Route::post('/', 'store')
            ->middleware(AdminPermissionEnum::COUNTRIES_MANAGE->middleware());

        Route::patch('{country}', 'update')
            ->middleware(AdminPermissionEnum::COUNTRIES_MANAGE->middleware());
        Route::delete('{record}', AdminTrashDeletionController::class)->whereNumber('record')->defaults('trashResource', 'countries')->middleware(AdminPermissionEnum::COUNTRIES_MANAGE->middleware());
    });
