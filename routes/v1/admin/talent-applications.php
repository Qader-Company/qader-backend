<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\AdminTalentApplicationController;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\AdminTalentApplicationEnumController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'abilities:access,admin',
    AdminPermissionEnum::TALENT_APPLICATIONS_VIEW->middleware(),
])
    ->controller(AdminTalentApplicationEnumController::class)
    ->group(function (): void {
        Route::get('scopes', 'scopes');
        Route::get('sort-options', 'sortOptions');
        Route::get('statuses', 'statuses');
        Route::get('evaluation-tip-priorities', 'evaluationTipPriorities');
        Route::get('reapply-policies', 'reapplyPolicies');
        Route::get('interview-types', 'interviewTypes');
        Route::get('interview-modes', 'interviewModes');
        Route::get('interview-statuses', 'interviewStatuses');
        Route::get('interview-slot-statuses', 'interviewSlotStatuses');
    });

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
            ->middleware('permission:'.AdminPermissionEnum::TALENT_APPLICATIONS_DECIDE->value.'|'.AdminPermissionEnum::INTERVIEWS_DECIDE->value);
        Route::post('{application}/schedule-interview', 'scheduleInterview')
            ->whereNumber('application')
            ->middleware('permission:'.AdminPermissionEnum::TALENT_APPLICATIONS_DECIDE->value.'|'.AdminPermissionEnum::INTERVIEWS_DECIDE->value);
        Route::post('{application}/accept', 'accept')
            ->whereNumber('application')
            ->middleware(AdminPermissionEnum::INTERVIEWS_DECIDE->middleware());
        Route::post('{application}/interview/slots', 'replaceInterviewSlots')
            ->whereNumber('application')
            ->middleware(AdminPermissionEnum::TALENT_APPLICATIONS_DECIDE->middleware());
    });
