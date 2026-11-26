<?php

use App\Modules\V1\Payments\Presentation\Http\Controllers\ClientPaymentController;
use Illuminate\Support\Facades\Route;

Route::post('{workspace}/talent-marketplace/{talent}/expert-opinion/checkout', [ClientPaymentController::class, 'checkout']);
Route::get('{workspace}/payments/{payment:reference}', [ClientPaymentController::class, 'show']);
