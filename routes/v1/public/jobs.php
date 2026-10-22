<?php

use App\Modules\V1\JobForms\Presentation\Http\Controllers\PublicJobController;
use Illuminate\Support\Facades\Route;

Route::controller(PublicJobController::class)->group(function (): void {
    Route::get('/', 'index');
    Route::get('{job}', 'show');
    Route::get('{job}/application-form', 'applicationForm');
    Route::post('{job}/submissions', 'submit')->middleware('throttle:public-job-submissions');
});
