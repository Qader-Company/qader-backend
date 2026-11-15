<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaidServicesSettings extends Settings
{
    public float $qader_expert_opinion_price;
    public string $qader_expert_opinion_currency;
    public float $detailed_rejection_feedback_price;
    public string $detailed_rejection_feedback_currency;
    public float $profile_expert_review_price;
    public string $profile_expert_review_currency;

    public static function group(): string
    {
        return 'paid_services';
    }
}
