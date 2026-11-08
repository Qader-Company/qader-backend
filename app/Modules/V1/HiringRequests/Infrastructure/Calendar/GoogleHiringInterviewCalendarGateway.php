<?php

namespace App\Modules\V1\HiringRequests\Infrastructure\Calendar;

use App\Modules\V1\HiringRequests\Application\Contracts\HiringInterviewCalendarGatewayInterface;
use App\Modules\V1\HiringRequests\Domain\Models\HiringInterview;
use App\Modules\V1\TalentApplications\Application\Data\CalendarMeetingData;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

class GoogleHiringInterviewCalendarGateway implements HiringInterviewCalendarGatewayInterface
{
    public function createOnlineMeeting(
        HiringInterview $interview,
        string $summary,
        array $attendeeEmails,
    ): CalendarMeetingData {
        [$calendar, $calendarId] = $this->calendar();
        $event = new Event([
            'summary' => $summary,
            'start' => new EventDateTime(['dateTime' => $interview->starts_at->toRfc3339String(), 'timeZone' => 'UTC']),
            'end' => new EventDateTime(['dateTime' => $interview->ends_at->toRfc3339String(), 'timeZone' => 'UTC']),
            'attendees' => collect($attendeeEmails)->filter()->unique()
                ->map(fn (string $email) => new EventAttendee(['email' => $email]))->values()->all(),
            'conferenceData' => new ConferenceData([
                'createRequest' => new CreateConferenceRequest([
                    'requestId' => (string) Str::uuid(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ]),
            ]),
        ]);

        try {
            $created = $calendar->events->insert($calendarId, $event, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => 'all',
            ]);
            $meetingUrl = $created->getHangoutLink()
                ?: collect($created->getConferenceData()?->getEntryPoints() ?? [])
                    ->firstWhere('entryPointType', 'video')?->getUri();
            if (! $created->getId() || ! $meetingUrl) {
                throw new ServiceUnavailableHttpException(null, __('hiring.calendar_meeting_not_created'));
            }

            return new CalendarMeetingData($created->getId(), $meetingUrl);
        } catch (ServiceUnavailableHttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ServiceUnavailableHttpException(null, __('hiring.calendar_unavailable'), $exception);
        }
    }

    public function deleteEvent(string $eventId): void
    {
        [$calendar, $calendarId] = $this->calendar();
        try {
            $calendar->events->delete($calendarId, $eventId, ['sendUpdates' => 'all']);
        } catch (Throwable $exception) {
            if ((int) $exception->getCode() !== 404) {
                throw new ServiceUnavailableHttpException(null, __('hiring.calendar_unavailable'), $exception);
            }
        }
    }

    /** @return array{GoogleCalendar, string} */
    private function calendar(): array
    {
        $credentials = (string) config('services.google.calendar.credentials');
        $calendarId = (string) config('services.google.calendar.id');
        $path = Str::startsWith($credentials, DIRECTORY_SEPARATOR) ? $credentials : base_path($credentials);
        if ($credentials === '' || $calendarId === '' || ! is_file($path)) {
            throw new ServiceUnavailableHttpException(null, __('hiring.calendar_not_configured'));
        }

        $client = new GoogleClient;
        $client->setAuthConfig($path);
        $client->setScopes([GoogleCalendar::CALENDAR_EVENTS]);
        if ($subject = config('services.google.calendar.subject')) {
            $client->setSubject((string) $subject);
        }

        return [new GoogleCalendar($client), $calendarId];
    }
}
