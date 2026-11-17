<?php

namespace App\Modules\V1\HelpRequests\Domain\ValueObjects;

enum HelpRequestTypeEnum: string
{
    case HIRING_SUPPORT = 'hiring_support';
    case FINDING_TALENT = 'finding_talent';
    case ONBOARDING_HELP = 'onboarding_help';
    case TECHNICAL_ISSUE = 'technical_issue';
    case OTHER = 'other';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
