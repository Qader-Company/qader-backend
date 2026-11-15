<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Cities\Presentation\Http\Controllers\AdminCityController;
use App\Modules\V1\Trash\Presentation\Http\Controllers\AdminTrashDeletionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminCityController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->middleware(AdminPermissionEnum::CITIES_VIEW->middleware());

        Route::post('/', 'store')
            ->middleware(AdminPermissionEnum::CITIES_MANAGE->middleware());

        Route::patch('{city}', 'update')
            ->middleware(AdminPermissionEnum::CITIES_MANAGE->middleware());
        Route::delete('{record}', AdminTrashDeletionController::class)->whereNumber('record')->defaults('trashResource', 'cities')->middleware(AdminPermissionEnum::CITIES_MANAGE->middleware());
    });
