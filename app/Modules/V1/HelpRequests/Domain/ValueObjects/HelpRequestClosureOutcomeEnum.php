<?php

namespace App\Modules\V1\HelpRequests\Domain\ValueObjects;

enum HelpRequestClosureOutcomeEnum: string
{
    case NEW_HIRING_REQUEST_CREATED = 'new_hiring_request_created';
    case CLIENT_REQUEST_CLARIFIED = 'client_request_clarified';
    case ISSUE_PROBLEM = 'issue_problem';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
