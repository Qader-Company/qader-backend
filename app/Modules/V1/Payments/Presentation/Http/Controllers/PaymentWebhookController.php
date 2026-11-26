<?php

namespace App\Modules\V1\Payments\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Payments\Application\Services\ProcessPaymentWebhookService;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(string $provider, Request $request, ProcessPaymentWebhookService $service)
    {
        $service->handle($provider, $request);

        return ApiResponse::message(__('payments.webhook_received'));
    }
}
