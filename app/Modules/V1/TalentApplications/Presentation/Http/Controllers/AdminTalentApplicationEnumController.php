<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\EvaluationTipPriorityEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewModeEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewSlotStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewTypeEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\ReapplyPolicyEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationListScopeEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationSortEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Support\ApiMetadata\EnumOptions;
use BackedEnum;
use Illuminate\Http\JsonResponse;

class AdminTalentApplicationEnumController extends Controller
{
    /** @response {"success":true,"data":[{"value":"all","label":"All Applications"},{"value":"my","label":"My Applications"}]} */
    public function scopes(): JsonResponse
    {
        return $this->response(TalentApplicationListScopeEnum::class, 'application_scopes');
    }

    /** @response {"success":true,"data":[{"value":"newest","label":"Newest"},{"value":"oldest","label":"Oldest"}]} */
    public function sortOptions(): JsonResponse
    {
        return $this->response(TalentApplicationSortEnum::class, 'application_sort_options');
    }

    /** @response {"success":true,"data":[{"value":"submitted","label":"Submitted"},{"value":"under_review","label":"Under Review"}]} */
    public function statuses(): JsonResponse
    {
        return $this->response(TalentApplicationStatusEnum::class, 'application_statuses');
    }

    /** @response {"success":true,"data":[{"value":"low","label":"Low"},{"value":"medium","label":"Medium"},{"value":"high","label":"High"}]} */
    public function evaluationTipPriorities(): JsonResponse
    {
        return $this->response(EvaluationTipPriorityEnum::class, 'evaluation_tip_priorities');
    }

    /** @response {"success":true,"data":[{"value":"allowed_after_period","label":"Allowed After Period"},{"value":"blacklisted","label":"Blacklisted"}]} */
    public function reapplyPolicies(): JsonResponse
    {
        return $this->response(ReapplyPolicyEnum::class, 'reapply_policies');
    }

    /** @response {"success":true,"data":[{"value":"hr","label":"HR"},{"value":"technical","label":"Technical"}]} */
    public function interviewTypes(): JsonResponse
    {
        return $this->response(InterviewTypeEnum::class, 'interview_types');
    }

    /** @response {"success":true,"data":[{"value":"online","label":"Online"},{"value":"offline","label":"Offline"}]} */
    public function interviewModes(): JsonResponse
    {
        return $this->response(InterviewModeEnum::class, 'interview_modes');
    }

    /** @response {"success":true,"data":[{"value":"awaiting_slot_selection","label":"Awaiting Slot Selection"},{"value":"confirmed","label":"Confirmed"}]} */
    public function interviewStatuses(): JsonResponse
    {
        return $this->response(InterviewStatusEnum::class, 'interview_statuses');
    }

    /** @response {"success":true,"data":[{"value":"available","label":"Available"},{"value":"selected","label":"Selected"}]} */
    public function interviewSlotStatuses(): JsonResponse
    {
        return $this->response(InterviewSlotStatusEnum::class, 'interview_slot_statuses');
    }

    /** @param class-string<BackedEnum> $enumClass */
    private function response(string $enumClass, string $translationKey): JsonResponse
    {
        return ApiResponse::success(EnumOptions::make($enumClass, $translationKey));
    }
}
