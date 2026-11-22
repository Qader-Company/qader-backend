<?php

namespace App\Modules\V1\Notifications\Application\Handlers;

use App\Modules\V1\HelpRequests\Domain\Models\HelpRequestEvent;
use App\Modules\V1\Notifications\Application\Services\NotificationRecipients;
use App\Modules\V1\Notifications\Application\Services\Notifier;

/** Turns rows of `help_request_events` into notifications. */
class HelpRequestNotificationHandler
{
    /** action => [type, party] */
    private const ACTIONS = [
        'assigned' => ['help_request_assigned', 'assignee'],
        'reassigned' => ['help_request_assigned', 'assignee'],
        'handling_started' => ['help_request_in_progress', 'client'],
        'closed' => ['help_request_closed', 'client'],
    ];

    public function __construct(
        private readonly Notifier $notifier,
        private readonly NotificationRecipients $recipients,
    ) {}

    public function handle(int $eventId): void
    {
        $event = HelpRequestEvent::query()->with('helpRequest.createdByMember.client')->find($eventId);
        $request = $event?->helpRequest;
        if ($request === null || ! isset(self::ACTIONS[$event->action])) {
            return;
        }

        [$type, $party] = self::ACTIONS[$event->action];
        $params = ['reference' => $request->reference];
        $translated = ['help_type' => 'enums.help_request_types.'.($request->help_type?->value ?? $request->help_type)];

        if ($party === 'assignee') {
            $this->notifier->send(
                [$this->recipients->admin($event->data['assigned_admin_id'] ?? $request->assigned_admin_id)],
                $type, $params, $translated,
                $request->id, null, $event->actor_user_id,
            );

            return;
        }

        $this->notifier->send(
            [$request->createdByMember?->client?->user_id],
            $type, $params, $translated,
            $request->id, $request->workspace_id, $event->actor_user_id,
        );
    }
}
