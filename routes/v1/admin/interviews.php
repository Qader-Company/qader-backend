<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\AdminInterviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminInterviewController::class)
    ->group(function (): void {
        Route::get('/', 'index')->middleware(AdminPermissionEnum::INTERVIEWS_VIEW->middleware());
        Route::get('{interview}', 'show')->whereNumber('interview')->middleware(AdminPermissionEnum::INTERVIEWS_VIEW->middleware());
        Route::post('{interview}/start', 'start')->whereNumber('interview')->middleware(AdminPermissionEnum::INTERVIEWS_MANAGE->middleware());
        Route::post('{interview}/complete', 'complete')->whereNumber('interview')->middleware(AdminPermissionEnum::INTERVIEWS_MANAGE->middleware());
        Route::put('{interview}/evaluation', 'saveEvaluation')->whereNumber('interview')->middleware(AdminPermissionEnum::INTERVIEWS_EVALUATE->middleware());
    });
