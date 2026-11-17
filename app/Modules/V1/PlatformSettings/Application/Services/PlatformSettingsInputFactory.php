<?php

namespace App\Modules\V1\PlatformSettings\Application\Services;

use App\Support\Http\Resources\AdminFormInput;

class PlatformSettingsInputFactory
{
    /** @var array<string, array<string, string>> */
    private const TYPES = [
        'hiring' => [
            'offer_response_duration_hours' => 'number',
            'post_interview_decision_duration_hours' => 'number',
            'talent_offer_no_response_limit' => 'number',
        ],
        'talent_evaluation' => [
            'interview_slot_rejection_limit' => 'number',
            'confirmed_interview_reschedule_limit' => 'number',
        ],
        'trash' => [
            'retention_days' => 'number',
        ],
        'platform' => [
            'support_response_business_days' => 'number',
            'support_email' => 'email',
            'support_phone' => 'tel',
            'support_whatsapp' => 'tel',
            'privacy_policy_url' => 'url',
            'terms_and_conditions_url' => 'url',
        ],
        'website' => [
            'footer_copyright_text_ar' => 'textarea',
            'footer_copyright_text_en' => 'textarea',
            'company_founded_year' => 'number',
            'referral_commission_percentage' => 'number',
            'company_location_ar' => 'text',
            'company_location_en' => 'text',
        ],
        'marketplace' => [
            'anonymous_talent_image' => 'file',
        ],
        'paid_services' => [
            'qader_expert_opinion_price' => 'number',
            'qader_expert_opinion_currency' => 'text',
            'detailed_rejection_feedback_price' => 'number',
            'detailed_rejection_feedback_currency' => 'text',
            'profile_expert_review_price' => 'number',
            'profile_expert_review_currency' => 'text',
        ],
    ];

    public function make(string $group, array $values): array
    {
        $types = self::TYPES[$group] ?? [];

        return [
            'inputs' => collect($values)
                ->map(fn (mixed $value, string $key): array => AdminFormInput::make(
                    $key,
                    $value,
                    $types[$key] ?? 'text',
                    __("settings.labels.{$key}"),
                ))
                ->values()
                ->all(),
        ];
    }
}
