<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Settings\HiringSettings;

class HiringRuntimeSettings
{
    public function __construct(private readonly HiringSettings $settings) {}

    public function offerResponseHours(): int
    {
        return max(1, $this->settings->offer_response_duration_hours);
    }

    public function decisionResponseHours(): int
    {
        return max(1, $this->settings->post_interview_decision_duration_hours);
    }

    public function talentNoResponseLimit(): int
    {
        return max(1, $this->settings->talent_offer_no_response_limit);
    }

    public function interviewDurationMinutes(): int
    {
        return max(1, (int) config('hiring.interview_duration_minutes', 60));
    }
}
