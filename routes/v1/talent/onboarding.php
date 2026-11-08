<?php

use App\Modules\V1\ProfessionalFields\Presentation\Http\Controllers\ProfessionalFormFieldController;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\TalentApplicationController;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use App\Modules\V1\Talents\Presentation\Http\Controllers\TalentOnboardingController;
use App\Modules\V1\Talents\Presentation\Http\Controllers\TalentResumeParserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TalentOnboardingController::class, 'show']);
Route::get('form-fields', [ProfessionalFormFieldController::class, 'index']);
Route::post('resume/parse', TalentResumeParserController::class)
    ->middleware('throttle:talent-resume-parse');
Route::patch('professional-field', [TalentOnboardingController::class, 'selectProfessionalField']);
Route::post('{step}', [TalentOnboardingController::class, 'saveStep'])
    ->whereIn('step', OnboardingStepEnum::values());
Route::get('application', [TalentApplicationController::class, 'show']);
