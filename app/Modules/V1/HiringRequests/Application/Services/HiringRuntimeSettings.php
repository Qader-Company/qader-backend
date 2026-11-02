<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

class HiringRuntimeSettings
{
    public function offerResponseHours(): int
    {
        return max(1, (int) config('hiring.offer_response_duration_hours', 48));
    }

    public function decisionResponseHours(): int
    {
        return max(1, (int) config('hiring.post_interview_decision_duration_hours', 24));
    }

    public function talentNoResponseLimit(): int
    {
        return max(1, (int) config('hiring.talent_offer_no_response_limit', 2));
    }

    public function interviewDurationMinutes(): int
    {
        return max(1, (int) config('hiring.interview_duration_minutes', 60));
    }
}
