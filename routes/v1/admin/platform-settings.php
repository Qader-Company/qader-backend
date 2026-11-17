<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\PlatformSettings\Presentation\Http\Controllers\PlatformSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(PlatformSettingsController::class)
    ->group(function (): void {
        Route::get('/', 'index')->middleware(AdminPermissionEnum::PLATFORM_SETTINGS_VIEW->middleware());

        Route::middleware(AdminPermissionEnum::PLATFORM_SETTINGS_UPDATE->middleware())->group(function (): void {
            Route::patch('hiring', 'updateHiring');
            Route::patch('talent-evaluation', 'updateTalentEvaluation');
            Route::patch('trash', 'updateTrash');
            Route::patch('platform', 'updatePlatform');
            Route::patch('website', 'updateWebsite');
            Route::patch('paid-services', 'updatePaidServices');
        });
    });
