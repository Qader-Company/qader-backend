<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentApplications\Application\Services\TalentEvaluationAccessService;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\TalentEvaluationPageResource;
use App\Modules\V1\Talents\Application\UseCases\GetTalentProfileUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TalentEvaluationController extends Controller
{
    /**
     * @response scenario="Evaluation available" {
     *   "success": true,
     *   "data": {
     *     "application_id": 1,
     *     "profile_review": {
     *       "id": 7,
     *       "evaluator": {
     *         "id": 3,
     *         "full_name": "Mariam Hassan",
     *         "display_name_ar": "مريم حسن",
     *         "email": "mariam.hassan@qadereg.com",
     *         "avatar": "https://api.qadereg.com/storage/avatars/mariam-hassan.jpg",
     *         "is_active": true
     *       },
     *       "ratings": {
     *         "technical_skills": 4,
     *         "communication_skills": 4,
     *         "problem_solving": 5,
     *         "soft_skills": 4
     *       },
     *       "average_rating": 4.25,
     *       "recommended_salary": {
     *         "min": "2500.0000",
     *         "max": "3200.0000",
     *         "currency": {
     *           "id": 1,
     *           "code": "USD",
     *           "name": "US Dollar",
     *           "symbol": "$",
     *           "is_active": true
     *         }
     *       },
     *       "reports": {
     *         "hr": "Clear communicator with strong collaboration skills.",
     *         "technical": "Strong technical fundamentals and clean problem-solving approach.",
     *         "psychology": "Calm under pressure and receptive to feedback."
     *       },
     *       "tips": [
     *         {
     *           "id": 1,
     *           "title": "Add measurable outcomes",
     *           "priority": "high",
     *           "description": "Quantify the impact of recent projects.",
     *           "display_order": 1
     *         }
     *       ],
     *       "created_at": "2026-11-01T09:30:00.000000Z",
     *       "updated_at": "2026-11-01T09:30:00.000000Z"
     *     },
     *     "interview_reviews": [
     *       {
     *         "interview_id": 11,
     *         "round_number": 1,
     *         "type": "technical",
     *         "completed_at": "2026-11-05T12:00:00.000000Z",
     *         "evaluation": {
     *           "id": 4,
     *           "evaluator": {
     *             "id": 3,
     *             "full_name": "Mariam Hassan",
     *             "display_name_ar": "مريم حسن",
     *             "email": "mariam.hassan@qadereg.com",
     *             "avatar": "https://api.qadereg.com/storage/avatars/mariam-hassan.jpg",
     *             "is_active": true
     *           },
     *           "ratings": {
     *             "technical_skills": 4,
     *             "communication_skills": 5,
     *             "problem_solving": 4,
     *             "soft_skills": 5
     *           },
     *           "average_rating": 4.5,
     *           "overall_score": 90,
     *           "reports": {
     *             "hr": "Professional and prepared.",
     *             "technical": "Solid system design answers.",
     *             "psychology": "Confident and collaborative."
     *           },
     *           "final_feedback": "Recommended for the talent pool.",
     *           "created_at": "2026-11-05T12:10:00.000000Z",
     *           "updated_at": "2026-11-05T12:10:00.000000Z"
     *         }
     *       }
     *     ]
     *   }
     * }
     * @response 403 scenario="Evaluation payment required" {"success":false,"message":"Payment is required to view your evaluation."}
     * @response 404 scenario="Evaluation unavailable" {"success":false,"message":"Your evaluation is not available yet."}
     */
    public function show(
        Request $request,
        GetTalentProfileUseCase $profiles,
        TalentEvaluationAccessService $access,
    ): JsonResponse {
        $profile = $profiles->execute($request->user()->id);
        $status = $access->status($profile);

        if ($status['is_locked']) {
            throw new AccessDeniedHttpException(__('talents.evaluation.payment_required'));
        }

        if (! $status['is_available']) {
            throw new NotFoundHttpException(__('talents.evaluation.not_available'));
        }

        $application = $access->evaluationApplication($profile)
            ?? throw new NotFoundHttpException(__('talents.evaluation.not_available'));
        $application->load([
            'evaluation.tips',
            'evaluation.currency.translations',
            'evaluation.evaluator.user',
            'interviews.evaluation.evaluator.user',
        ]);

        return ApiResponse::success(new TalentEvaluationPageResource($application));
    }
}
