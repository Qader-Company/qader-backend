<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\HiringRequests\Presentation\Http\Controllers\AdminHiringRequestController;
use App\Modules\V1\HiringRequests\Presentation\Http\Controllers\AdminHiringRequestEnumController;
use App\Modules\V1\HiringRequests\Presentation\Http\Controllers\AdminHiringWorkflowController;
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

    Route::controller(AdminHiringWorkflowController::class)->prefix('items/{item}')->whereNumber('item')->group(function (): void {
        Route::post('offers', 'sendOffer')->middleware(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->middleware());
        Route::post('offers/respond-on-behalf', 'respondOnBehalf')->middleware(AdminPermissionEnum::HIRING_REQUESTS_RESPOND_ON_BEHALF->middleware());
        Route::post('interview/schedule', 'proposeInterview')->middleware(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->middleware());
        Route::post('interview/respond-on-behalf', 'respondToInterviewOnBehalf')->middleware(AdminPermissionEnum::HIRING_REQUESTS_RESPOND_ON_BEHALF->middleware());
        Route::post('interview/confirm', 'confirmInterview')->middleware(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->middleware());
        Route::post('interview/start', 'startInterview')->middleware(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->middleware());
        Route::post('interview/complete', 'completeInterview')->middleware(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->middleware());
        Route::post('decision-on-behalf', 'decisionOnBehalf')->middleware(AdminPermissionEnum::HIRING_REQUESTS_RESPOND_ON_BEHALF->middleware());
        Route::delete('decision-on-behalf', 'withdrawDecisionOnBehalf')->middleware(AdminPermissionEnum::HIRING_REQUESTS_RESPOND_ON_BEHALF->middleware());
        Route::post('hold', 'hold')->middleware(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->middleware());
        Route::post('resume', 'resume')->middleware(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->middleware());
        Route::post('cancel', 'cancel')->middleware(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->middleware());
    });
});
