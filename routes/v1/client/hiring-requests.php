<?php

use App\Modules\V1\HiringRequests\Presentation\Http\Controllers\ClientHiringRequestController;
use App\Modules\V1\HiringRequests\Presentation\Http\Controllers\ClientHiringWorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('{workspace}/hiring-requests', [ClientHiringRequestController::class, 'index']);
Route::post('{workspace}/hiring-requests', [ClientHiringRequestController::class, 'store']);
Route::get('{workspace}/hiring-requests/{hiringRequest}', [ClientHiringRequestController::class, 'show'])->whereNumber('hiringRequest');
Route::get('{workspace}/hiring-interviews/{interview}', [ClientHiringWorkflowController::class, 'interview'])->whereNumber(['workspace', 'interview']);
Route::post('{workspace}/hiring-interviews/{interview}/accept-schedule', [ClientHiringWorkflowController::class, 'acceptInterviewSchedule'])->whereNumber(['workspace', 'interview']);
Route::post('{workspace}/hiring-interviews/{interview}/request-schedule-change', [ClientHiringWorkflowController::class, 'requestInterviewScheduleChange'])->whereNumber(['workspace', 'interview']);
Route::post('{workspace}/hiring-interviews/{interview}/apologize', [ClientHiringWorkflowController::class, 'apologizeForInterview'])->whereNumber(['workspace', 'interview']);
Route::post('{workspace}/hiring-request-items/{item}/decision', [ClientHiringWorkflowController::class, 'decision'])->whereNumber(['workspace', 'item']);
Route::post('{workspace}/hiring-request-items/{item}/hold', [ClientHiringWorkflowController::class, 'hold'])->whereNumber(['workspace', 'item']);
Route::post('{workspace}/hiring-request-items/{item}/resume', [ClientHiringWorkflowController::class, 'resume'])->whereNumber(['workspace', 'item']);
Route::post('{workspace}/hiring-request-items/{item}/cancel', [ClientHiringWorkflowController::class, 'cancel'])->whereNumber(['workspace', 'item']);
