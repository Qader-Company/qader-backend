<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentApplications\Application\Services\AdminApplicationActor;
use App\Modules\V1\TalentApplications\Application\Services\TalentApplicationQueryService;
use App\Modules\V1\TalentApplications\Application\Services\TalentApplicationReviewWorkflow;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\AssignTalentApplicationReviewerRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\ListAdminTalentApplicationsRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\RejectTalentApplicationRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\ReplaceTalentInterviewSlotsRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\SaveTalentEvaluationRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\ScheduleTalentInterviewRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\AdminSummaryResource;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\AdminTalentApplicationListResource;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\AdminTalentApplicationResource;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\InterviewResource;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\TalentEvaluationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Admin
 *
 * @subgroup Talent Applications
 */
class AdminTalentApplicationController extends Controller
{
    public function index(
        ListAdminTalentApplicationsRequest $request,
        AdminApplicationActor $actors,
        TalentApplicationQueryService $queries,
    ): JsonResponse {
        $result = $queries->paginate($request->validated(), $actors->resolve($request->user()));
        $paginator = $result['applications'];

        return ApiResponse::success([
            'applications' => AdminTalentApplicationListResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'summary' => $result['summary'],
            ],
        ]);
    }

    public function reviewers(TalentApplicationQueryService $queries): JsonResponse
    {
        return ApiResponse::success(AdminSummaryResource::collection($queries->reviewers()));
    }

    public function show(
        Request $request,
        int $application,
        AdminApplicationActor $actors,
        TalentApplicationQueryService $queries,
    ): JsonResponse {
        $model = $queries->findForDetails($application);
        $model->setAttribute('available_actions', $queries->availableActions(
            $model,
            $request->user(),
            $actors->resolve($request->user()),
        ));

        return ApiResponse::success(new AdminTalentApplicationResource($model));
    }

    public function reviewerHistory(int $application, TalentApplicationQueryService $queries): JsonResponse
    {
        $history = $queries->reviewerHistory($application)->map(fn ($assignment): array => [
            'id' => $assignment->id,
            'reviewer' => (new AdminSummaryResource($assignment->reviewer))->resolve(request()),
            'assigned_by' => (new AdminSummaryResource($assignment->assignedBy))->resolve(request()),
            'assigned_at' => $assignment->assigned_at,
            'ended_at' => $assignment->ended_at,
        ]);

        return ApiResponse::success($history);
    }

    public function activity(int $application, TalentApplicationQueryService $queries): JsonResponse
    {
        $events = $queries->activity($application)->map(fn ($event): array => [
            'id' => $event->id,
            'action' => $event->action,
            'data' => $event->data,
            'admin' => (new AdminSummaryResource($event->admin))->resolve(request()),
            'created_at' => $event->created_at,
        ]);

        return ApiResponse::success($events);
    }

    public function assignReviewer(
        AssignTalentApplicationReviewerRequest $request,
        AdminApplicationActor $actors,
        TalentApplicationReviewWorkflow $workflow,
    ): JsonResponse {
        $workflow->assignReviewer(
            $request->validated('application_ids'),
            $request->integer('reviewer_admin_id'),
            $actors->resolve($request->user()),
        );

        return ApiResponse::message(__('talents.review.reviewer_assigned'));
    }

    public function startReview(
        Request $request,
        int $application,
        AdminApplicationActor $actors,
        TalentApplicationReviewWorkflow $workflow,
        TalentApplicationQueryService $queries,
    ): JsonResponse {
        $workflow->startReview($application, $actors->resolve($request->user()));
        $model = $queries->findForDetails($application);
        $model->setAttribute('available_actions', $queries->availableActions(
            $model,
            $request->user(),
            $actors->resolve($request->user()),
        ));

        return ApiResponse::updated(new AdminTalentApplicationResource($model));
    }

    public function saveEvaluation(
        SaveTalentEvaluationRequest $request,
        int $application,
        AdminApplicationActor $actors,
        TalentApplicationReviewWorkflow $workflow,
    ): JsonResponse {
        $evaluation = $workflow->saveEvaluation(
            $application,
            $request->validated(),
            $actors->resolve($request->user()),
        );

        return ApiResponse::updated(new TalentEvaluationResource($evaluation), __('talents.review.evaluation_saved'));
    }

    public function reject(
        RejectTalentApplicationRequest $request,
        int $application,
        AdminApplicationActor $actors,
        TalentApplicationReviewWorkflow $workflow,
        TalentApplicationQueryService $queries,
    ): JsonResponse {
        $workflow->reject($application, $request->validated(), $actors->resolve($request->user()));

        return ApiResponse::updated(
            new AdminTalentApplicationResource($queries->findForDetails($application)),
            __('talents.review.application_rejected'),
        );
    }

    public function scheduleInterview(
        ScheduleTalentInterviewRequest $request,
        int $application,
        AdminApplicationActor $actors,
        TalentApplicationReviewWorkflow $workflow,
    ): JsonResponse {
        $interview = $workflow->scheduleInterview(
            $application,
            $request->validated(),
            $actors->resolve($request->user()),
        );

        return ApiResponse::created(new InterviewResource($interview), __('talents.review.interview_scheduled'));
    }

    /**
     * @response {"success":true,"message":"Replacement interview times saved successfully.","data":{"id":1,"type":"technical","status":"awaiting_slot_selection","interviewers":[],"slots":[{"id":4,"mode":"online","start":"2026-11-05T10:00:00.000000Z","end":"2026-11-05T11:00:00.000000Z","location":null,"latitude":null,"longitude":null,"status":"available"}],"google_calendar_event_id":null,"meeting_url":null,"created_at":"2026-10-21T09:00:00.000000Z"}}
     */
    public function replaceInterviewSlots(
        ReplaceTalentInterviewSlotsRequest $request,
        int $application,
        AdminApplicationActor $actors,
        TalentApplicationReviewWorkflow $workflow,
    ): JsonResponse {
        $interview = $workflow->replaceInterviewSlots(
            $application,
            $request->validated(),
            $actors->resolve($request->user()),
        );

        return ApiResponse::updated(
            new InterviewResource($interview),
            __('talents.review.replacement_slots_saved'),
        );
    }

    public function downloadCv(int $application, TalentApplicationQueryService $queries): StreamedResponse
    {
        $media = $queries->findForDetails($application)->getFirstMedia('submitted_cv');
        abort_if($media === null, 404, __('talents.review.cv_not_found'));

        return Storage::disk($media->disk)->download($media->getPathRelativeToRoot(), $media->file_name);
    }
}
