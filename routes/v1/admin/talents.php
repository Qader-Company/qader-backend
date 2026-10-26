<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\AdminTalentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminTalentController::class)
    ->group(function (): void {
        Route::get('/', 'index')->middleware(AdminPermissionEnum::TALENTS_VIEW->middleware());
        Route::get('{talent}', 'show')->whereNumber('talent')->middleware(AdminPermissionEnum::TALENTS_VIEW->middleware());
        Route::post('{talent}/add-to-blacklist', 'addToBlacklist')->whereNumber('talent')->middleware(AdminPermissionEnum::TALENTS_MANAGE_BLACKLIST->middleware());
        Route::post('{talent}/remove-from-blacklist', 'removeFromBlacklist')->whereNumber('talent')->middleware(AdminPermissionEnum::TALENTS_MANAGE_BLACKLIST->middleware());
        Route::patch('{talent}/edit-reapply', 'editReapply')->whereNumber('talent')->middleware(AdminPermissionEnum::TALENTS_MANAGE_REAPPLY->middleware());
    });
