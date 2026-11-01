<?php

use App\Modules\V1\HiringRequests\Presentation\Http\Controllers\ClientHiringRequestController;
use Illuminate\Support\Facades\Route;

Route::get('{workspace}/hiring-requests/options', [ClientHiringRequestController::class, 'options']);
Route::get('{workspace}/hiring-requests', [ClientHiringRequestController::class, 'index']);
Route::post('{workspace}/hiring-requests', [ClientHiringRequestController::class, 'store']);
Route::get('{workspace}/hiring-requests/{hiringRequest}', [ClientHiringRequestController::class, 'show'])->whereNumber('hiringRequest');
Route::put('{workspace}/hiring-requests/{hiringRequest}', [ClientHiringRequestController::class, 'update'])->whereNumber('hiringRequest');
Route::post('{workspace}/hiring-requests/{hiringRequest}/submit', [ClientHiringRequestController::class, 'submit'])->whereNumber('hiringRequest');
