<?php

use App\Modules\V1\HiringRequests\Presentation\Http\Controllers\TalentHiringController;
use Illuminate\Support\Facades\Route;

Route::controller(TalentHiringController::class)->group(function (): void {
    Route::get('hiring-offers/options', 'options');
    Route::get('hiring-offers', 'index');
    Route::get('hiring-offers/{item}', 'show')->whereNumber('item');
    Route::post('hiring-offers/{item}/accept', 'accept')->whereNumber('item');
    Route::post('hiring-offers/{item}/reject', 'reject')->whereNumber('item');
    Route::post('hiring-offers/{item}/counter', 'counter')->whereNumber('item');
    Route::get('hiring-interviews/{interview}', 'interview')->whereNumber('interview');
    Route::post('hiring-interviews/{interview}/accept-schedule', 'acceptInterviewSchedule')->whereNumber('interview');
    Route::post('hiring-interviews/{interview}/request-schedule-change', 'requestInterviewScheduleChange')->whereNumber('interview');
    Route::post('hiring-interviews/{interview}/apologize', 'apologizeForInterview')->whereNumber('interview');
    Route::post('hiring-request-items/{item}/decision', 'decision')->whereNumber('item');
    Route::delete('hiring-request-items/{item}/decision', 'withdrawDecision')->whereNumber('item');
});
