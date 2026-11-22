<?php

namespace App\Modules\V1\Notifications\Application\Handlers;

use App\Modules\V1\Notifications\Application\Services\NotificationRecipients;
use App\Modules\V1\Notifications\Application\Services\Notifier;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplicationEvent;

/** Talent application review notifications (admin actions are events; talent slot actions are sent explicitly). */
class TalentApplicationNotificationHandler
{
    /** Admin-side events => [type, party] */
    private const EVENTS = [
        'reviewer_assigned' => ['talent_application_assigned', 'reviewer'],
        'reviewer_reassigned' => ['talent_application_assigned', 'reviewer'],
        'interview_scheduled' => ['talent_interview_slots_available', 'talent'],
        'replacement_interview_slots_offered' => ['talent_interview_slots_available', 'talent'],
        'application_accepted' => ['talent_application_accepted', 'talent'],
        'application_rejected' => ['talent_application_rejected', 'talent'],
    ];

    /** Talent-side actions => notification type for the assigned reviewer */
    public const TALENT_ACTIONS = [
        'slot_selected' => 'talent_interview_slot_selected',
        'slots_rejected' => 'talent_interview_slots_rejected',
        'slot_cancelled' => 'talent_interview_cancelled',
    ];

    public function __construct(
        private readonly Notifier $notifier,
        private readonly NotificationRecipients $recipients,
    ) {}

    public function handleEvent(int $eventId): void
    {
        $event = TalentApplicationEvent::query()->find($eventId);
        if ($event === null || ! isset(self::EVENTS[$event->action])) {
            return;
        }
        $application = $this->application($event->application_id);
        if ($application === null) {
            return;
        }

        [$type, $party] = self::EVENTS[$event->action];
        $actorUserId = $this->recipients->admin($event->admin_id);

        if ($party === 'reviewer') {
            $this->notifyReviewer($application, $type, $event->data['reviewer_admin_id'] ?? $application->assigned_admin_id, $actorUserId);

            return;
        }

        $this->notifier->send(
            [$application->talent?->user_id], $type, [], [],
            $application->id, null, $actorUserId,
        );
    }

    public function handleTalentAction(int $applicationId, string $action): void
    {
        $application = $this->application($applicationId);
        if ($application === null || ! isset(self::TALENT_ACTIONS[$action])) {
            return;
        }

        $this->notifyReviewer($application, self::TALENT_ACTIONS[$action], $application->assigned_admin_id, $application->talent?->user_id);
    }

    private function notifyReviewer(TalentApplication $application, string $type, ?int $adminId, ?int $actorUserId): void
    {
        $user = $application->talent?->user;

        $this->notifier->send(
            [$this->recipients->admin($adminId)],
            $type,
            [
                'talent' => trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')),
                'talent_code' => $application->talent?->talent_code,
            ],
            [],
            $application->id,
            null,
            $actorUserId,
        );
    }

    private function application(int $id): ?TalentApplication
    {
        return TalentApplication::query()->with('talent.user')->find($id);
    }
}
