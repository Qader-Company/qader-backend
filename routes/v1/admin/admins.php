<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Admins\Presentation\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->middleware(AdminPermissionEnum::ADMINS_VIEW->middleware());

        Route::get('{admin}', 'show')
            ->whereNumber('admin')
            ->middleware(AdminPermissionEnum::ADMINS_VIEW->middleware());

        Route::post('/', 'store')
            ->middleware(AdminPermissionEnum::ADMINS_MANAGE->middleware());

        Route::patch('{admin}', 'update')
            ->whereNumber('admin')
            ->middleware(AdminPermissionEnum::ADMINS_MANAGE->middleware());

        Route::patch('{admin}/status', 'updateStatus')
            ->whereNumber('admin')
            ->middleware(AdminPermissionEnum::ADMINS_MANAGE->middleware());
    });
