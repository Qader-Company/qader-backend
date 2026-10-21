<?php

namespace App\Modules\V1\TalentApplications\Application\Contracts;

use App\Modules\V1\TalentApplications\Application\Data\CalendarMeetingData;
use App\Modules\V1\TalentApplications\Domain\Models\InterviewSlot;

interface InterviewCalendarGatewayInterface
{
    /** @param array<int, string> $attendeeEmails */
    public function createOnlineMeeting(
        InterviewSlot $slot,
        string $summary,
        array $attendeeEmails,
    ): CalendarMeetingData;

    public function deleteEvent(string $eventId): void;
}
