<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Currencies\Presentation\Http\Controllers\AdminCurrencyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminCurrencyController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->middleware(AdminPermissionEnum::CURRENCIES_VIEW->middleware());

        Route::post('/', 'store')
            ->middleware(AdminPermissionEnum::CURRENCIES_MANAGE->middleware());

        Route::patch('{record}', 'update')
            ->middleware(AdminPermissionEnum::CURRENCIES_MANAGE->middleware());
    });
