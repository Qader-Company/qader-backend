<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\AdminTalentReviewFaqController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminTalentReviewFaqController::class)
    ->group(function (): void {
        Route::get('screen-states', 'screenStates')->middleware(AdminPermissionEnum::TALENT_REVIEW_FAQS_VIEW->middleware());
        Route::get('/', 'index')->middleware(AdminPermissionEnum::TALENT_REVIEW_FAQS_VIEW->middleware());
        Route::get('{faq}', 'show')->middleware(AdminPermissionEnum::TALENT_REVIEW_FAQS_VIEW->middleware());
        Route::post('/', 'store')->middleware(AdminPermissionEnum::TALENT_REVIEW_FAQS_MANAGE->middleware());
        Route::patch('{faq}', 'update')->middleware(AdminPermissionEnum::TALENT_REVIEW_FAQS_MANAGE->middleware());
        Route::delete('{faq}', 'destroy')->middleware(AdminPermissionEnum::TALENT_REVIEW_FAQS_MANAGE->middleware());
    });
