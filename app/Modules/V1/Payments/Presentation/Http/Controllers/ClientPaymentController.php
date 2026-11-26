<?php

namespace App\Modules\V1\Payments\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AccessControl\Application\Services\WorkspaceAccessService;
use App\Modules\V1\AccessControl\Domain\ValueObjects\ClientPermissionEnum;
use App\Modules\V1\Payments\Application\Services\CreatePaymentCheckoutService;
use App\Modules\V1\Payments\Domain\Models\Payment;
use App\Modules\V1\Payments\Presentation\Http\Requests\CreatePaymentCheckoutRequest;
use App\Modules\V1\Payments\Presentation\Http\Resources\PaymentCheckoutResource;
use App\Modules\V1\Payments\Presentation\Http\Resources\PaymentResource;
use App\Modules\V1\TalentMarketplace\Application\Services\TalentExpertOpinionAccessService;
use App\Modules\V1\TalentMarketplace\Application\Services\TalentMarketplaceService;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ClientPaymentController extends Controller
{
    /**
     * @response scenario="Checkout created" {"success":true,"data":{"payment":{"reference":"e0b12a2f-b56a-47fb-b8ef-554b28c67dda","service":"qader_expert_opinion","amount":"250.0000","currency":"EGP","status":"processing","paid_at":null,"created_at":"2026-11-25T12:00:00.000000Z"},"checkout":{"mode":"redirect","checkout_url":"https://gateway.example/checkout/123","client_token":null,"public_config":{},"expires_at":"2026-11-25T12:30:00+00:00"}}}
     * @response 503 scenario="Production gateway not configured" {"success":false,"message":"Online payments are not configured yet."}
     */
    public function checkout(
        CreatePaymentCheckoutRequest $request,
        Workspace $workspace,
        Talent $talent,
        WorkspaceAccessService $access,
        TalentMarketplaceService $marketplace,
        TalentExpertOpinionAccessService $opinions,
        CreatePaymentCheckoutService $payments,
    ) {
        $access->authorize($request->user(), $workspace, ClientPermissionEnum::MARKETPLACE_VIEW->value);
        $talent = $marketplace->details($request->user(), $workspace, $talent);
        if ($opinions->reportApplication($talent) === null) {
            throw new NotFoundHttpException(__('marketplace.expert_opinion_not_available'));
        }

        return ApiResponse::success(new PaymentCheckoutResource(
            $payments->forClient($request->user(), $workspace, $talent),
        ));
    }

    /**
     * @response scenario="Payment status" {"success":true,"data":{"reference":"e0b12a2f-b56a-47fb-b8ef-554b28c67dda","service":"qader_expert_opinion","amount":"250.0000","currency":"EGP","status":"paid","paid_at":"2026-11-25T12:02:00.000000Z","created_at":"2026-11-25T12:00:00.000000Z"}}
     */
    public function show(Request $request, Workspace $workspace, Payment $payment, WorkspaceAccessService $access)
    {
        $access->authorize($request->user(), $workspace, ClientPermissionEnum::MARKETPLACE_VIEW->value);
        abort_unless((int) $payment->owner_client_id === (int) $workspace->owner_client_id, 404);

        return ApiResponse::success(new PaymentResource($payment));
    }
}
