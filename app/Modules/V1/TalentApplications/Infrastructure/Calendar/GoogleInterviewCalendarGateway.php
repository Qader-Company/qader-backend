<?php

namespace App\Modules\V1\TalentApplications\Infrastructure\Calendar;

use App\Modules\V1\TalentApplications\Application\Contracts\InterviewCalendarGatewayInterface;
use App\Modules\V1\TalentApplications\Application\Data\CalendarMeetingData;
use App\Modules\V1\TalentApplications\Domain\Models\InterviewSlot;
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

class GoogleInterviewCalendarGateway implements InterviewCalendarGatewayInterface
{
    public function createOnlineMeeting(
        InterviewSlot $slot,
        string $summary,
        array $attendeeEmails,
    ): CalendarMeetingData {
        [$calendar, $calendarId] = $this->calendar();

        $event = new Event([
            'summary' => $summary,
            'start' => new EventDateTime([
                'dateTime' => $slot->starts_at->toRfc3339String(),
                'timeZone' => 'UTC',
            ]),
            'end' => new EventDateTime([
                'dateTime' => $slot->ends_at->toRfc3339String(),
                'timeZone' => 'UTC',
            ]),
            'attendees' => collect($attendeeEmails)
                ->filter()
                ->unique()
                ->map(fn (string $email) => new EventAttendee(['email' => $email]))
                ->values()
                ->all(),
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
                if ($created->getId()) {
                    $calendar->events->delete($calendarId, $created->getId(), ['sendUpdates' => 'all']);
                }

                throw new ServiceUnavailableHttpException(null, __('talents.interview.meeting_not_created'));
            }

            return new CalendarMeetingData($created->getId(), $meetingUrl);
        } catch (ServiceUnavailableHttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ServiceUnavailableHttpException(
                null,
                __('talents.interview.calendar_unavailable'),
                $exception,
            );
        }
    }

    public function deleteEvent(string $eventId): void
    {
        [$calendar, $calendarId] = $this->calendar();

        try {
            $calendar->events->delete($calendarId, $eventId, ['sendUpdates' => 'all']);
        } catch (Throwable $exception) {
            if ((int) $exception->getCode() === 404) {
                return;
            }

            throw new ServiceUnavailableHttpException(
                null,
                __('talents.interview.calendar_unavailable'),
                $exception,
            );
        }
    }

    /** @return array{GoogleCalendar, string} */
    private function calendar(): array
    {
        $credentials = (string) config('services.google.calendar.credentials');
        $calendarId = (string) config('services.google.calendar.id');

        if ($credentials === '' || $calendarId === '') {
            throw new ServiceUnavailableHttpException(null, __('talents.interview.calendar_not_configured'));
        }

        $credentialsPath = Str::startsWith($credentials, DIRECTORY_SEPARATOR)
            ? $credentials
            : base_path($credentials);

        if (! is_file($credentialsPath)) {
            throw new ServiceUnavailableHttpException(null, __('talents.interview.calendar_not_configured'));
        }

        $client = new GoogleClient;
        $client->setAuthConfig($credentialsPath);
        $client->setScopes([GoogleCalendar::CALENDAR_EVENTS]);

        if ($subject = config('services.google.calendar.subject')) {
            $client->setSubject((string) $subject);
        }

        return [new GoogleCalendar($client), $calendarId];
    }
}
