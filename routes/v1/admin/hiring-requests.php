<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\HiringRequests\Presentation\Http\Controllers\AdminHiringRequestController;
use App\Modules\V1\HiringRequests\Presentation\Http\Controllers\AdminHiringRequestEnumController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])->group(function (): void {
    Route::get('options', AdminHiringRequestEnumController::class)
        ->middleware(AdminPermissionEnum::HIRING_REQUESTS_VIEW->middleware());

    Route::controller(AdminHiringRequestController::class)->group(function (): void {
        Route::get('/', 'index')->middleware(AdminPermissionEnum::HIRING_REQUESTS_VIEW->middleware());
        Route::get('assignees', 'assignees')->middleware(AdminPermissionEnum::HIRING_REQUESTS_ASSIGN->middleware());
        Route::get('{hiringRequest}', 'show')->whereNumber('hiringRequest')->middleware(AdminPermissionEnum::HIRING_REQUESTS_VIEW->middleware());
        Route::post('{hiringRequest}/assign', 'assign')->whereNumber('hiringRequest')->middleware(AdminPermissionEnum::HIRING_REQUESTS_ASSIGN->middleware());
        Route::post('{hiringRequest}/reassign', 'reassign')->whereNumber('hiringRequest')->middleware(AdminPermissionEnum::HIRING_REQUESTS_ASSIGN->middleware());
        Route::post('{hiringRequest}/start', 'start')->whereNumber('hiringRequest')->middleware(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->middleware());
    });
});
