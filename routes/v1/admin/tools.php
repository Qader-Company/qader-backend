<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Tools\Presentation\Http\Controllers\AdminToolController;
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
    });
