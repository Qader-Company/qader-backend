<?php

use App\Modules\V1\Payments\Presentation\Http\Controllers\TalentPaymentController;
use Illuminate\Support\Facades\Route;

Route::post('applications/{application}/evaluation/checkout', [TalentPaymentController::class, 'checkout']);
Route::get('payments/{payment:reference}', [TalentPaymentController::class, 'show']);
