<?php

use App\Modules\V1\Website\Presentation\Http\Controllers\PublicHiringSupportRequestController;
use Illuminate\Support\Facades\Route;

Route::post('/', [PublicHiringSupportRequestController::class, 'store'])
    ->middleware('throttle:public-hiring-support-requests');
