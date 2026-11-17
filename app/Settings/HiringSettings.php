<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class HiringSettings extends Settings
{
    public int $offer_response_duration_hours;
    public int $post_interview_decision_duration_hours;
    public int $talent_offer_no_response_limit;

    public static function group(): string
    {
        return 'hiring';
    }
}
