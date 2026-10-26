<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentApplications\Application\Services\AdminApplicationActor;
use App\Modules\V1\TalentApplications\Application\Services\AdminInterviewQueryService;
use App\Modules\V1\TalentApplications\Application\Services\AdminInterviewWorkflow;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\ListAdminInterviewsRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\SaveInterviewEvaluationRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\InterviewEvaluationResource;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\InterviewResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin
 *
 * @subgroup Interviews
 */
class AdminInterviewController extends Controller
{
    public function index(
        ListAdminInterviewsRequest $request,
        AdminApplicationActor $actors,
        AdminInterviewQueryService $queries,
    ): JsonResponse {
        $paginator = $queries->paginate(
            $request->validated(),
            $actors->resolve($request->user()),
            $request->user(),
        );

        return ApiResponse::success([
            'interviews' => InterviewResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(
        Request $request,
        int $interview,
        AdminApplicationActor $actors,
        AdminInterviewQueryService $queries,
    ): JsonResponse {
        $model = $queries->findForDetails(
            $interview,
            $request->user(),
            $actors->resolve($request->user()),
        );

        return ApiResponse::success([
            'interview' => (new InterviewResource($model))->resolve($request),
            'interview_history' => InterviewResource::collection(
                $model->application->interviews,
            )->resolve($request),
        ]);
    }

    public function start(
        Request $request,
        int $interview,
        AdminApplicationActor $actors,
        AdminInterviewWorkflow $workflow,
    ): JsonResponse {
        return ApiResponse::updated(
            new InterviewResource($workflow->start($interview, $actors->resolve($request->user()))),
            __('talents.interview.started'),
        );
    }

    public function complete(
        Request $request,
        int $interview,
        AdminApplicationActor $actors,
        AdminInterviewWorkflow $workflow,
    ): JsonResponse {
        return ApiResponse::updated(
            new InterviewResource($workflow->complete($interview, $actors->resolve($request->user()))),
            __('talents.interview.completed'),
        );
    }

    public function saveEvaluation(
        SaveInterviewEvaluationRequest $request,
        int $interview,
        AdminApplicationActor $actors,
        AdminInterviewWorkflow $workflow,
    ): JsonResponse {
        return ApiResponse::updated(
            new InterviewEvaluationResource($workflow->saveEvaluation(
                $interview,
                $request->validated(),
                $actors->resolve($request->user()),
            )),
            __('talents.interview.evaluation_saved'),
        );
    }
}
