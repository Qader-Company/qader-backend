<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\HelpRequests\Presentation\Http\Controllers\AdminHelpRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminHelpRequestController::class)
    ->group(function (): void {
        Route::get('options', 'options')->middleware(AdminPermissionEnum::HELP_REQUESTS_VIEW->middleware());
        Route::get('/', 'index')->middleware(AdminPermissionEnum::HELP_REQUESTS_VIEW->middleware());
        Route::get('assignees', 'assignees')->middleware(AdminPermissionEnum::HELP_REQUESTS_ASSIGN->middleware());
        Route::post('assign', 'assign')->middleware(AdminPermissionEnum::HELP_REQUESTS_ASSIGN->middleware());

        Route::prefix('{helpRequest}')->whereNumber('helpRequest')->group(function (): void {
            Route::get('/', 'show')->middleware(AdminPermissionEnum::HELP_REQUESTS_VIEW->middleware());
            Route::post('reassign', 'reassign')->middleware(AdminPermissionEnum::HELP_REQUESTS_ASSIGN->middleware());
            Route::post('start', 'start')->middleware(AdminPermissionEnum::HELP_REQUESTS_HANDLE->middleware());
            Route::post('notes', 'storeNote')->middleware(AdminPermissionEnum::HELP_REQUESTS_HANDLE->middleware());
            Route::get('hiring-requests', 'hiringRequests')->middleware(AdminPermissionEnum::HELP_REQUESTS_HANDLE->middleware());
            Route::post('close', 'close')->middleware(AdminPermissionEnum::HELP_REQUESTS_HANDLE->middleware());
        });
    });
