<?php

use App\Modules\V1\Talents\Presentation\Http\Controllers\TalentMediaController;
use App\Modules\V1\Talents\Presentation\Http\Controllers\TalentProfileController;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\TalentEvaluationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TalentProfileController::class, 'show']);
Route::patch('/', [TalentProfileController::class, 'update']);
Route::get('evaluation', [TalentEvaluationController::class, 'show']);
Route::get('media/{media}/download', [TalentMediaController::class, 'download'])
    ->name('talent.profile.media.download');
