<?php

use App\Modules\V1\Cities\Presentation\Http\Controllers\CityController;
use App\Modules\V1\Countries\Presentation\Http\Controllers\CountryController;
use App\Modules\V1\Industries\Presentation\Http\Controllers\IndustryController;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Controllers\ProfessionalFieldController;
use Illuminate\Support\Facades\Route;

Route::get('countries', [CountryController::class, 'index']);
Route::get('cities', [CityController::class, 'index']);
Route::get('industries', [IndustryController::class, 'index']);
Route::get('professional-fields', [ProfessionalFieldController::class, 'index']);
