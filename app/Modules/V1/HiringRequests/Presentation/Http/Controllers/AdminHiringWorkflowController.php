<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\HiringRequests\Application\Services\AdminHiringRequestActor;
use App\Modules\V1\HiringRequests\Application\Services\HiringDecisionWorkflow;
use App\Modules\V1\HiringRequests\Application\Services\HiringInterviewWorkflow;
use App\Modules\V1\HiringRequests\Application\Services\HiringItemControlWorkflow;
use App\Modules\V1\HiringRequests\Application\Services\HiringOfferWorkflow;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringDecisionEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Presentation\Http\Requests\SaveHiringOfferRequest;
use App\Modules\V1\HiringRequests\Presentation\Http\Resources\HiringInterviewResource;
use App\Modules\V1\HiringRequests\Presentation\Http\Resources\HiringRequestItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * @group Admin
 *
 * @subgroup Hiring Requests
 */
class AdminHiringWorkflowController extends Controller
{
    public function sendOffer(SaveHiringOfferRequest $request, int $item, AdminHiringRequestActor $actors, HiringOfferWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new HiringRequestItemResource($workflow->sendClientOffer($request->user(), $actors->resolve($request->user()), $item, $request->validated())), __('hiring.offer_sent'));
    }

    public function respondOnBehalf(Request $request, int $item, AdminHiringRequestActor $actors, HiringOfferWorkflow $workflow): JsonResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['accepted', 'rejected'])], 'reason' => ['nullable', 'string', 'max:2000']]);
        if ($data['decision'] === 'rejected' && blank($data['reason'] ?? null)) {
            throw ValidationException::withMessages(['reason' => [__('hiring.rejection_reason_required')]]);
        }

        return ApiResponse::updated(new HiringRequestItemResource($workflow->respondOnBehalf($request->user(), $actors->resolve($request->user()), $item, HiringOfferStatusEnum::from($data['decision']), $data['reason'] ?? null)), __('hiring.offer_response_saved'));
    }

    public function proposeInterview(Request $request, int $item, AdminHiringRequestActor $actors, HiringInterviewWorkflow $workflow): JsonResponse
    {
        $data = $request->validate(['starts_at' => ['required', 'date', 'after:now']]);

        return ApiResponse::updated(new HiringInterviewResource($workflow->propose($actors->resolve($request->user()), $request->user(), $item, $data['starts_at'])), __('hiring.interview_proposed'));
    }

    public function confirmInterview(Request $request, int $item, AdminHiringRequestActor $actors, HiringInterviewWorkflow $workflow): JsonResponse
    {
        $data = $request->validate(['meeting_url' => ['nullable', 'url:http,https', 'max:2048']]);

        return ApiResponse::updated(new HiringInterviewResource($workflow->confirm($actors->resolve($request->user()), $request->user(), $item, $data['meeting_url'] ?? null)), __('hiring.interview_confirmed'));
    }

    public function startInterview(Request $request, int $item, AdminHiringRequestActor $actors, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new HiringInterviewResource($workflow->start($actors->resolve($request->user()), $request->user(), $item)), __('hiring.interview_started'));
    }

    public function completeInterview(Request $request, int $item, AdminHiringRequestActor $actors, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new HiringInterviewResource($workflow->complete($actors->resolve($request->user()), $request->user(), $item)), __('hiring.interview_completed'));
    }

    public function decisionOnBehalf(Request $request, int $item, AdminHiringRequestActor $actors, HiringDecisionWorkflow $workflow): JsonResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::enum(HiringDecisionEnum::class)], 'reason' => ['nullable', 'string', 'max:2000']]);

        return ApiResponse::updated(new HiringRequestItemResource($workflow->submitOnBehalf($request->user(), $actors->resolve($request->user()), $item, HiringDecisionEnum::from($data['decision']), $data['reason'] ?? null)), __('hiring.decision_saved'));
    }

    public function withdrawDecisionOnBehalf(Request $request, int $item, AdminHiringRequestActor $actors, HiringDecisionWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new HiringRequestItemResource($workflow->withdrawOnBehalf($request->user(), $actors->resolve($request->user()), $item)), __('hiring.decision_withdrawn'));
    }

    public function hold(Request $request, int $item, AdminHiringRequestActor $actors, HiringItemControlWorkflow $workflow): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);

        return ApiResponse::updated(new HiringRequestItemResource($workflow->holdForAdmin($request->user(), $actors->resolve($request->user()), $item, $data['reason'] ?? null)), __('hiring.item_held'));
    }

    public function resume(Request $request, int $item, AdminHiringRequestActor $actors, HiringItemControlWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new HiringRequestItemResource($workflow->resumeForAdmin($request->user(), $actors->resolve($request->user()), $item)), __('hiring.item_resumed'));
    }

    public function cancel(Request $request, int $item, AdminHiringRequestActor $actors, HiringItemControlWorkflow $workflow): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);

        return ApiResponse::updated(new HiringRequestItemResource($workflow->cancelForAdmin($request->user(), $actors->resolve($request->user()), $item, $data['reason'] ?? null)), __('hiring.item_cancelled'));
    }
}
