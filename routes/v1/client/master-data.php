<?php

use App\Modules\V1\Industries\Presentation\Http\Controllers\IndustryController;
use Illuminate\Support\Facades\Route;

Route::get('industries', [IndustryController::class, 'index']);
