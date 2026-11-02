<?php

namespace App\Modules\V1\HiringRequests\Application\Contracts;

use App\Modules\V1\HiringRequests\Domain\Models\HiringInterview;
use App\Modules\V1\TalentApplications\Application\Data\CalendarMeetingData;

interface HiringInterviewCalendarGatewayInterface
{
    /** @param array<int, string> $attendeeEmails */
    public function createOnlineMeeting(
        HiringInterview $interview,
        string $summary,
        array $attendeeEmails,
    ): CalendarMeetingData;

    public function deleteEvent(string $eventId): void;
}
