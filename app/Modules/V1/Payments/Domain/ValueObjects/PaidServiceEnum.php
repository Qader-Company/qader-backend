<?php

namespace App\Modules\V1\Payments\Domain\ValueObjects;

use App\Settings\PaidServicesSettings;

enum PaidServiceEnum: string
{
    case QADER_EXPERT_OPINION = 'qader_expert_opinion';
    case DETAILED_REJECTION_FEEDBACK = 'detailed_rejection_feedback';
    case PROFILE_EXPERT_REVIEW = 'profile_expert_review';

    /** @return array{amount: string, currency: string} */
    public function price(PaidServicesSettings $settings): array
    {
        return match ($this) {
            self::QADER_EXPERT_OPINION => [
                'amount' => number_format($settings->qader_expert_opinion_price, 4, '.', ''),
                'currency' => strtoupper($settings->qader_expert_opinion_currency),
            ],
            self::DETAILED_REJECTION_FEEDBACK => [
                'amount' => number_format($settings->detailed_rejection_feedback_price, 4, '.', ''),
                'currency' => strtoupper($settings->detailed_rejection_feedback_currency),
            ],
            self::PROFILE_EXPERT_REVIEW => [
                'amount' => number_format($settings->profile_expert_review_price, 4, '.', ''),
                'currency' => strtoupper($settings->profile_expert_review_currency),
            ],
        };
    }
}
