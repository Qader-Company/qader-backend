<?php

use App\Modules\V1\Talents\Presentation\Http\Controllers\TalentMediaController;
use App\Modules\V1\Talents\Presentation\Http\Controllers\TalentProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TalentProfileController::class, 'show']);
Route::patch('/', [TalentProfileController::class, 'update']);
Route::get('media/{media}/download', [TalentMediaController::class, 'download'])
    ->name('talent.profile.media.download');
