<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\EvaluationTipPriorityEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewModeEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewSlotStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewTypeEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationScreenStateEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Http\JsonResponse;

class TalentApplicationEnumController extends Controller
{
    /**
     * List the Talent Application enum options used by the Talent Portal.
     *
     * Admin-only options (list scopes, sorting, reapply policies) are intentionally excluded.
     *
     * @response {"success":true,"data":{"application_statuses":[{"value":"submitted","label":"Submitted"}],"screen_states":[{"value":"under_review","label":"Under Review"}],"evaluation_tip_priorities":[{"value":"low","label":"Low"}],"interview_types":[{"value":"hr","label":"HR"}],"interview_modes":[{"value":"online","label":"Online"}],"interview_statuses":[{"value":"awaiting_slot_selection","label":"Awaiting Slot Selection"}],"interview_slot_statuses":[{"value":"available","label":"Available"}]}}
     */
    public function options(): JsonResponse
    {
        return ApiResponse::success([
            'application_statuses' => EnumOptions::make(TalentApplicationStatusEnum::class, 'application_statuses'),
            'screen_states' => EnumOptions::make(TalentApplicationScreenStateEnum::class, 'screen_states'),
            'evaluation_tip_priorities' => EnumOptions::make(EvaluationTipPriorityEnum::class, 'evaluation_tip_priorities'),
            'interview_types' => EnumOptions::make(InterviewTypeEnum::class, 'interview_types'),
            'interview_modes' => EnumOptions::make(InterviewModeEnum::class, 'interview_modes'),
            'interview_statuses' => EnumOptions::make(InterviewStatusEnum::class, 'interview_statuses'),
            'interview_slot_statuses' => EnumOptions::make(InterviewSlotStatusEnum::class, 'interview_slot_statuses'),
        ]);
    }
}
