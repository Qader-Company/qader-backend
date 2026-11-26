<?php

use App\Modules\V1\Payments\Presentation\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('{provider}', PaymentWebhookController::class)->middleware('throttle:payment-webhooks');
