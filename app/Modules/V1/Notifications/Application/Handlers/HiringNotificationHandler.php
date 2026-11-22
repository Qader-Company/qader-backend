<?php

namespace App\Modules\V1\Notifications\Application\Handlers;

use App\Modules\V1\HiringRequests\Application\Services\HiringItemControlWorkflow;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestEvent;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\Notifications\Application\Services\NotificationRecipients;
use App\Modules\V1\Notifications\Application\Services\Notifier;

/** Turns rows of `hiring_request_events` into notifications. */
class HiringNotificationHandler
{
    /** action => [notification type, parties to notify] */
    private const ACTIONS = [
        'submitted' => ['hiring_request_submitted', ['client']],
        'assigned' => ['hiring_request_assigned', ['assignee']],
        'reassigned' => ['hiring_request_assigned', ['assignee']],
        'offer_sent' => ['hiring_offer_received', ['talent']],
        'offer_accepted' => ['hiring_offer_accepted', ['client', 'admin']],
        'offer_rejected' => ['hiring_offer_rejected', ['client', 'admin']],
        'offer_countered' => ['hiring_counter_offer_received', ['client', 'admin']],
        'counter_offer_accepted' => ['hiring_counter_offer_accepted', ['talent', 'client', 'admin']],
        'offer_responded_on_behalf' => ['hiring_offer_responded_on_behalf', ['talent', 'client']],
        'offer_response_deadline_expired' => ['hiring_offer_expired', ['talent', 'client', 'admin']],
        'interview_proposed' => ['hiring_interview_proposed', ['talent', 'client']],
        'interview_rescheduled' => ['hiring_interview_rescheduled', ['talent', 'client']],
        'interview_schedule_responded' => ['hiring_interview_response_received', ['admin']],
        'interview_confirmed' => ['hiring_interview_confirmed', ['talent', 'client']],
        'interview_completed' => ['hiring_decision_required', ['client']],
        'decision_recorded' => ['hiring_decision_made', ['talent', 'client', 'admin']],
        'decision_recorded_on_behalf' => ['hiring_decision_made', ['talent', 'client']],
        'decision_withdrawn' => ['hiring_decision_withdrawn', ['client', 'admin']],
        'item_held' => ['hiring_item_on_hold', ['talent', 'client', 'admin']],
        'item_resumed' => ['hiring_item_resumed', ['talent', 'client', 'admin']],
        'item_cancelled' => ['hiring_item_cancelled', ['talent', 'client', 'admin']],
    ];

    public function __construct(
        private readonly Notifier $notifier,
        private readonly NotificationRecipients $recipients,
    ) {}

    public function handle(int $eventId): void
    {
        $event = HiringRequestEvent::query()
            ->with(['hiringRequest.workspace', 'item.talent', 'item.jobTitle'])
            ->find($eventId);
        if ($event === null || ! isset(self::ACTIONS[$event->action]) || $event->hiringRequest === null) {
            return;
        }

        [$type, $parties] = self::ACTIONS[$event->action];
        $data = $event->data ?? [];
        // Pausing every item of a deactivated workspace is announced once to the owner (workspace_deactivated);
        // only the talent needs to know about its own paused item.
        if ($event->action === 'item_held' && ($data['source'] ?? null) === HiringItemControlWorkflow::WORKSPACE_HOLD_SOURCE) {
            $parties = ['talent'];
        }

        $request = $event->hiringRequest;
        $params = $this->params($request, $event->item);
        $translated = $this->translatedParams($event->action, $data);

        foreach ($parties as $party) {
            $recipient = $party === 'assignee'
                ? ['ids' => [$this->recipients->admin($data['assigned_admin_id'] ?? null)], 'target_id' => $request->id, 'workspace_id' => null]
                : $this->recipients->hiringParty($party, $request, $event->item);

            $this->notifier->send(
                $recipient['ids'], $type, $params, $translated,
                $recipient['target_id'], $recipient['workspace_id'], $event->actor_user_id,
            );
        }
    }

    /** Shared text params for every hiring notification. */
    public function params(HiringRequest $request, ?HiringRequestItem $item): array
    {
        return [
            'reference' => $item?->reference ?? $request->reference,
            'company' => $request->workspace?->name,
            'job_title' => $item?->jobTitle?->name,
            'talent_code' => $item?->talent?->talent_code,
        ];
    }

    private function translatedParams(string $action, array $data): array
    {
        return match ($action) {
            'offer_responded_on_behalf' => ['decision' => 'enums.hiring_offer_statuses.'.($data['decision'] ?? '')],
            'decision_recorded', 'decision_recorded_on_behalf' => ['decision' => 'enums.hiring_decisions.'.($data['decision'] ?? '')],
            'interview_schedule_responded' => ['response' => 'enums.hiring_interview_responses.'.($data['response'] ?? '')],
            default => [],
        };
    }
}
