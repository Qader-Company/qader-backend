<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\AdminTalentApplicationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminTalentApplicationController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_VIEW->middleware());
        Route::get('reviewers', 'reviewers')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_VIEW->middleware());
        Route::post('assign-reviewer', 'assignReviewer')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_ASSIGN_REVIEWER->middleware());

        Route::get('{application}', 'show')
            ->whereNumber('application')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_VIEW->middleware());
        Route::get('{application}/cv', 'downloadCv')
            ->name('admin.talent-applications.cv.download')
            ->whereNumber('application')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_VIEW->middleware());
        Route::get('{application}/reviewer-history', 'reviewerHistory')
            ->whereNumber('application')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_VIEW->middleware());
        Route::get('{application}/activity', 'activity')
            ->whereNumber('application')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_VIEW->middleware());
        Route::post('{application}/start-review', 'startReview')
            ->whereNumber('application')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_REVIEW->middleware());
        Route::put('{application}/evaluation', 'saveEvaluation')
            ->whereNumber('application')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_REVIEW->middleware());
        Route::post('{application}/reject', 'reject')
            ->whereNumber('application')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_DECIDE->middleware());
        Route::post('{application}/schedule-interview', 'scheduleInterview')
            ->whereNumber('application')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_DECIDE->middleware());
        Route::post('{application}/interview/slots', 'replaceInterviewSlots')
            ->whereNumber('application')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_DECIDE->middleware());
    });
