<?php

namespace App\Modules\V1\TalentApplications\Application\Data;

readonly class CalendarMeetingData
{
    public function __construct(
        public string $eventId,
        public string $meetingUrl,
    ) {}
}
