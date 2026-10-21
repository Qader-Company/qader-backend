<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentApplications\Application\Services\TalentInterviewWorkflow;
use App\Modules\V1\TalentApplications\Application\Services\TalentReviewFaqService;
use App\Modules\V1\TalentApplications\Application\UseCases\GetTalentApplicationUseCase;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\RejectInterviewSlotsRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\TalentApplicationResource;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\TalentInterviewResource;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\TalentReviewFaqResource;
use App\Modules\V1\Talents\Application\UseCases\GetTalentProfileUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TalentApplicationController extends Controller
{
    /**
     * @response 404 scenario="Application not submitted" {"success":false,"message":"No application has been submitted yet."}
     * @response scenario="Interview slot required" {"success":true,"data":{"id":1,"status":"interview_required","screen_state":"interview_slot_required","submitted_at":"2026-10-19T08:45:00.000000Z","review_decided_at":"2026-10-21T09:00:00.000000Z","reapply_at":null,"can_reapply":false,"is_blacklisted":false,"profile_completion_at_submission":100,"snapshot_version":1,"snapshot_captured_at":"2026-10-19T08:45:00.000000Z","submitted_cv":{"id":10,"file_name":"cv.pdf","download_url":"http://localhost:8000/api/v1/talent/profile/media/10/download"},"interview":{"id":1,"type":"technical","status":"awaiting_slot_selection","available_slots":[{"id":1,"mode":"online","start":"2026-11-01T10:00:00.000000Z","end":"2026-11-01T11:00:00.000000Z","duration_minutes":60,"location":null,"latitude":null,"longitude":null,"status":"available","is_selectable":true}],"selected_slot":null,"meeting_url":null,"slot_rejections":{"used":0,"maximum":2,"remaining":2},"talent_reschedule_used_at":null,"actions":{"can_select_slot":true,"can_reject_all_slots":true,"can_cancel_and_reschedule":false,"can_join":false},"created_at":"2026-10-21T09:00:00.000000Z"},"faqs":[{"id":5,"screen_states":["interview_slot_required","awaiting_new_interview_slots","interview_confirmed","interview_completed"],"question":"What happens after I pass the initial review?","answer":"You have passed the initial review. The next step is to choose one of the available interview time slots.","display_order":4,"is_active":true}]}}
     */
    public function show(
        Request $request,
        GetTalentProfileUseCase $profiles,
        GetTalentApplicationUseCase $applications,
        TalentReviewFaqService $faqs,
    ): JsonResponse {
        $profile = $profiles->execute($request->user()->id);
        $application = $applications->execute($profile->id);
        $application->load(['media', 'blacklistEntry', 'interview.slots']);
        $resource = new TalentApplicationResource($application);
        $data = $resource->resolve($request);
        $data['faqs'] = TalentReviewFaqResource::collection(
            $faqs->activeForScreen($data['screen_state']),
        )->resolve($request);

        return ApiResponse::success($data);
    }

    /**
     * @urlParam slot integer required The available interview slot ID. Example: 1
     *
     * @response {"success":true,"message":"Your interview time has been confirmed successfully.","data":{"id":1,"type":"technical","status":"confirmed","available_slots":[],"selected_slot":{"id":1,"mode":"online","start":"2026-11-01T10:00:00.000000Z","end":"2026-11-01T11:00:00.000000Z","duration_minutes":60,"location":null,"latitude":null,"longitude":null,"status":"selected","is_selectable":false},"meeting_url":"https://meet.google.com/abc-defg-hij","slot_rejections":{"used":0,"maximum":2,"remaining":2},"talent_reschedule_used_at":null,"actions":{"can_select_slot":false,"can_reject_all_slots":false,"can_cancel_and_reschedule":true,"can_join":true},"created_at":"2026-10-21T09:00:00.000000Z"}}
     * @response 409 scenario="Slot unavailable" {"success":false,"message":"This interview time is no longer available for selection."}
     */
    public function selectInterviewSlot(
        Request $request,
        int $slot,
        GetTalentProfileUseCase $profiles,
        TalentInterviewWorkflow $workflow,
    ): JsonResponse {
        $profile = $profiles->execute($request->user()->id);
        $interview = $workflow->selectSlot($profile, $slot);

        return ApiResponse::updated(
            new TalentInterviewResource($interview),
            __('talents.interview.slot_confirmed'),
        );
    }

    /**
     * @response {"success":true,"message":"The available interview times were rejected. The Qader team will provide new times.","data":{"id":1,"type":"technical","status":"awaiting_new_slots","available_slots":[],"selected_slot":null,"meeting_url":null,"slot_rejections":{"used":1,"maximum":2,"remaining":1},"talent_reschedule_used_at":null,"actions":{"can_select_slot":false,"can_reject_all_slots":false,"can_cancel_and_reschedule":false,"can_join":false},"created_at":"2026-10-21T09:00:00.000000Z"}}
     * @response 409 scenario="Slots cannot be rejected" {"success":false,"message":"The available interview times can no longer be rejected."}
     */
    public function rejectInterviewSlots(
        RejectInterviewSlotsRequest $request,
        GetTalentProfileUseCase $profiles,
        TalentInterviewWorkflow $workflow,
    ): JsonResponse {
        $profile = $profiles->execute($request->user()->id);
        $interview = $workflow->rejectAllSlots($profile, $request->string('reason')->toString());

        return ApiResponse::updated(
            new TalentInterviewResource($interview),
            __('talents.interview.slots_rejected'),
        );
    }

    /**
     * @response {"success":true,"message":"Your interview was cancelled. The Qader team will provide new times.","data":{"id":1,"type":"technical","status":"awaiting_new_slots","available_slots":[],"selected_slot":null,"meeting_url":null,"slot_rejections":{"used":0,"maximum":2,"remaining":2},"talent_reschedule_used_at":"2026-10-21T10:00:00.000000Z","actions":{"can_select_slot":false,"can_reject_all_slots":false,"can_cancel_and_reschedule":false,"can_join":false},"created_at":"2026-10-21T09:00:00.000000Z"}}
     * @response 409 scenario="Cancellation unavailable" {"success":false,"message":"This interview cannot be cancelled or the one reschedule chance has already been used."}
     */
    public function cancelInterview(
        Request $request,
        GetTalentProfileUseCase $profiles,
        TalentInterviewWorkflow $workflow,
    ): JsonResponse {
        $profile = $profiles->execute($request->user()->id);
        $interview = $workflow->cancelConfirmedSlot($profile);

        return ApiResponse::updated(
            new TalentInterviewResource($interview),
            __('talents.interview.cancelled'),
        );
    }
}
