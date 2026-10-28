<?php

use App\Modules\V1\ProfessionalFields\Presentation\Http\Controllers\ProfessionalFormFieldController;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\TalentApplicationController;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use App\Modules\V1\Talents\Presentation\Http\Controllers\TalentOnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TalentOnboardingController::class, 'show']);
Route::get('form-fields', [ProfessionalFormFieldController::class, 'index']);
Route::patch('professional-field', [TalentOnboardingController::class, 'selectProfessionalField']);
Route::post('{step}', [TalentOnboardingController::class, 'saveStep'])
    ->whereIn('step', OnboardingStepEnum::values());
Route::get('application', [TalentApplicationController::class, 'show']);
