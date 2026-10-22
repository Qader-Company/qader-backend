<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\JobForms\Presentation\Http\Controllers\AdminJobFormController;
use App\Modules\V1\JobForms\Presentation\Http\Controllers\AdminJobFormEnumController;
use App\Modules\V1\JobForms\Presentation\Http\Controllers\AdminJobSubmissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])->group(function (): void {
    Route::controller(AdminJobFormEnumController::class)->group(function (): void {
        Route::get('publishing-statuses', 'publishingStatuses');
        Route::get('submission-statuses', 'submissionStatuses');
        Route::get('work-types', 'workTypes');
        Route::get('work-modes', 'workModes');
        Route::get('experience-ranges', 'experienceRanges');
    })->middleware(AdminPermissionEnum::JOB_FORMS_VIEW->middleware());
    Route::controller(AdminJobFormController::class)->group(function (): void {
        Route::get('/', 'index')->middleware(AdminPermissionEnum::JOB_FORMS_VIEW->middleware());
        Route::post('/', 'store')->middleware(AdminPermissionEnum::JOB_FORMS_MANAGE->middleware());
        Route::get('{jobForm}', 'show')->middleware(AdminPermissionEnum::JOB_FORMS_VIEW->middleware());
        Route::patch('{jobForm}', 'update')->middleware(AdminPermissionEnum::JOB_FORMS_MANAGE->middleware());
        Route::post('{jobForm}/publish', 'publish')->middleware(AdminPermissionEnum::JOB_FORMS_MANAGE->middleware());
        Route::patch('{jobForm}/activation', 'activation')->middleware(AdminPermissionEnum::JOB_FORMS_MANAGE->middleware());
        Route::delete('{jobForm}', 'destroy')->middleware(AdminPermissionEnum::JOB_FORMS_MANAGE->middleware());
    });
    Route::controller(AdminJobSubmissionController::class)->prefix('{jobForm}/submissions')->group(function (): void {
        Route::get('/', 'index')->middleware(AdminPermissionEnum::JOB_FORM_SUBMISSIONS_VIEW->middleware());
        Route::get('export', 'export')->middleware(AdminPermissionEnum::JOB_FORM_SUBMISSIONS_VIEW->middleware());
        Route::get('{submission}', 'show')->middleware(AdminPermissionEnum::JOB_FORM_SUBMISSIONS_VIEW->middleware());
        Route::get('{submission}/cv', 'downloadCv')->middleware(AdminPermissionEnum::JOB_FORM_SUBMISSIONS_VIEW->middleware());
        Route::delete('{submission}', 'destroy')->middleware(AdminPermissionEnum::JOB_FORM_SUBMISSIONS_DELETE->middleware());
        Route::post('{submission}/start-hiring', 'startHiring')->middleware(AdminPermissionEnum::JOB_FORM_SUBMISSIONS_START_HIRING->middleware());
    });
});
