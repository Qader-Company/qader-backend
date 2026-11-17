<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class TalentEvaluationSettings extends Settings
{
    public int $interview_slot_rejection_limit;
    public int $confirmed_interview_reschedule_limit;

    public static function group(): string
    {
        return 'talent_evaluation';
    }
}
