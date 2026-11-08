<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Presentation\Http\Controllers\AccessControlController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AccessControlController::class)
    ->group(function (): void {
        Route::get('permissions', 'permissions')
            ->middleware(AdminPermissionEnum::ACCESS_CONTROL_VIEW->middleware());

        Route::get('roles', 'roles')
            ->middleware(AdminPermissionEnum::ACCESS_CONTROL_VIEW->middleware());

        Route::get('roles/{role}', 'showRole')
            ->whereNumber('role')
            ->middleware(AdminPermissionEnum::ACCESS_CONTROL_VIEW->middleware());

        Route::post('roles', 'storeRole')
            ->middleware(AdminPermissionEnum::ACCESS_CONTROL_MANAGE_ROLES->middleware());

        Route::match(['put', 'patch'], 'roles/{role}', 'updateRole')
            ->whereNumber('role')
            ->middleware(AdminPermissionEnum::ACCESS_CONTROL_MANAGE_ROLES->middleware());

        Route::patch('roles/{role}/status', 'updateRoleStatus')
            ->whereNumber('role')
            ->middleware(AdminPermissionEnum::ACCESS_CONTROL_MANAGE_ROLES->middleware());

        Route::delete('roles/{role}', 'destroyRole')
            ->whereNumber('role')
            ->middleware(AdminPermissionEnum::ACCESS_CONTROL_MANAGE_ROLES->middleware());

    });
