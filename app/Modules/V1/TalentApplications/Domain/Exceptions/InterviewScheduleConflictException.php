<?php

namespace App\Modules\V1\TalentApplications\Domain\Exceptions;

use RuntimeException;

class InterviewScheduleConflictException extends RuntimeException
{
    /** @param array<int, array<string, mixed>> $conflicts */
    public function __construct(
        public readonly array $conflicts,
        string $message,
    ) {
        parent::__construct($message);
    }
}
