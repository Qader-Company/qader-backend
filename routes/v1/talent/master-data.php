<?php

use App\Modules\V1\Cities\Presentation\Http\Controllers\CityController;
use App\Modules\V1\Countries\Presentation\Http\Controllers\CountryController;
use App\Modules\V1\Currencies\Presentation\Http\Controllers\CurrencyController;
use App\Modules\V1\JobTitles\Presentation\Http\Controllers\JobTitleController;
use App\Modules\V1\Languages\Presentation\Http\Controllers\LanguageController;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Controllers\ProfessionalFieldController;
use App\Modules\V1\Skills\Presentation\Http\Controllers\SkillController;
use App\Modules\V1\Talents\Presentation\Http\Controllers\TalentEnumController;
use App\Modules\V1\Tools\Presentation\Http\Controllers\ToolController;
use Illuminate\Support\Facades\Route;

Route::get('countries', [CountryController::class, 'index']);
Route::get('cities', [CityController::class, 'index']);
Route::get('languages', [LanguageController::class, 'index']);
Route::get('currencies', [CurrencyController::class, 'index']);
Route::get('professional-fields', [ProfessionalFieldController::class, 'index']);
Route::get('job-titles', [JobTitleController::class, 'index']);
Route::get('skills', [SkillController::class, 'index']);
Route::get('tools', [ToolController::class, 'index']);

Route::get('genders', [TalentEnumController::class, 'genders']);
Route::get('religions', [TalentEnumController::class, 'religions']);
Route::get('language-proficiencies', [TalentEnumController::class, 'languageProficiencies']);
Route::get('seniority-levels', [TalentEnumController::class, 'seniorityLevels']);
Route::get('work-types', [TalentEnumController::class, 'workTypes']);
Route::get('work-modes', [TalentEnumController::class, 'workModes']);

