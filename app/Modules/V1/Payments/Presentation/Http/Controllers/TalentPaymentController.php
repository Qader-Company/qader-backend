<?php

namespace App\Modules\V1\Payments\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Payments\Application\Services\CreatePaymentCheckoutService;
use App\Modules\V1\Payments\Domain\Models\Payment;
use App\Modules\V1\Payments\Presentation\Http\Requests\CreatePaymentCheckoutRequest;
use App\Modules\V1\Payments\Presentation\Http\Resources\PaymentCheckoutResource;
use App\Modules\V1\Payments\Presentation\Http\Resources\PaymentResource;
use App\Modules\V1\TalentApplications\Application\Services\TalentEvaluationAccessService;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Http\Request;

class TalentPaymentController extends Controller
{
    /**
     * @header Idempotency-Key 18cf61e5-26a9-40a1-8d50-a4890ccab791
     *
     * @response scenario="Checkout created" {"success":true,"data":{"payment":{"reference":"7b846da6-4cf8-42a3-aebc-e47d97d79261","service":"detailed_rejection_feedback","amount":"100.0000","currency":"EGP","status":"processing","paid_at":null,"created_at":"2026-11-25T12:00:00.000000Z"},"checkout":{"mode":"redirect","checkout_url":"https://gateway.example/checkout/456","client_token":null,"public_config":{},"expires_at":"2026-11-25T12:30:00+00:00"}}}
     * @response 404 scenario="Application has no purchasable report" {"success":false,"message":"This evaluation is not available for purchase."}
     */
    public function checkout(
        CreatePaymentCheckoutRequest $request,
        TalentApplication $application,
        TalentEvaluationAccessService $evaluations,
        CreatePaymentCheckoutService $payments,
    ) {
        $talentId = Talent::query()->where('user_id', $request->user()->id)->value('id');
        abort_unless((int) $application->talent_id === (int) $talentId, 404);
        abort_unless($evaluations->hasReport($application), 404);

        return ApiResponse::success(new PaymentCheckoutResource(
            $payments->forTalent($request->user(), $application, $request->idempotencyKey()),
        ));
    }

    public function show(Request $request, Payment $payment)
    {
        abort_unless((int) $payment->payer_user_id === (int) $request->user()->id, 404);

        return ApiResponse::success(new PaymentResource($payment));
    }
}
