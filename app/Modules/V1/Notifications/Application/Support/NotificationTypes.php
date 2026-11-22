<?php

namespace App\Modules\V1\Notifications\Application\Support;

/**
 * Every notification `type` a portal can receive. The apps map each type to a page
 * and open the record in `target_id` (and `workspace_id` on the client portal).
 */
class NotificationTypes
{
    private const HIRING_SHARED = [
        'hiring_offer_expired',
        'hiring_interview_reminder',
        'hiring_interview_starting',
        'hiring_decision_made',
        'hiring_item_on_hold',
        'hiring_item_resumed',
        'hiring_item_cancelled',
    ];

    private const RECORD_ACTIVITY = ['record_created', 'record_deleted', 'record_restored', 'record_removed'];

    /** @return array<string, array<int, string>> */
    public static function byPortal(): array
    {
        return [
            'talent' => [
                'hiring_offer_received',
                'hiring_counter_offer_accepted',
                'hiring_offer_responded_on_behalf',
                'hiring_offer_deadline_reminder',
                'hiring_interview_proposed',
                'hiring_interview_rescheduled',
                'hiring_interview_confirmed',
                ...self::HIRING_SHARED,
                'talent_interview_slots_available',
                'talent_interview_reminder',
                'talent_interview_starting',
                'talent_application_accepted',
                'talent_application_rejected',
            ],
            'client' => [
                'hiring_request_submitted',
                'hiring_offer_accepted',
                'hiring_offer_rejected',
                'hiring_counter_offer_received',
                'hiring_counter_offer_accepted',
                'hiring_offer_responded_on_behalf',
                'hiring_offer_deadline_reminder',
                'hiring_interview_proposed',
                'hiring_interview_rescheduled',
                'hiring_interview_confirmed',
                'hiring_decision_required',
                'hiring_decision_deadline_reminder',
                'hiring_decision_withdrawn',
                ...self::HIRING_SHARED,
                'help_request_in_progress',
                'help_request_closed',
                'workspace_deactivated',
                'workspace_activated',
                ...self::RECORD_ACTIVITY,
            ],
            'admin' => [
                'hiring_request_assigned',
                'hiring_offer_accepted',
                'hiring_offer_rejected',
                'hiring_counter_offer_received',
                'hiring_counter_offer_accepted',
                'hiring_interview_response_received',
                'hiring_decision_withdrawn',
                ...self::HIRING_SHARED,
                'talent_application_assigned',
                'talent_interview_slot_selected',
                'talent_interview_slots_rejected',
                'talent_interview_cancelled',
                'talent_interview_reminder',
                'talent_interview_starting',
                'help_request_assigned',
                ...self::RECORD_ACTIVITY,
            ],
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(string $portal): array
    {
        return array_map(fn (string $type): array => [
            'value' => $type,
            // The type's title, with a generic word for the record placeholder (e.g. "New record added").
            'label' => __("notifications.{$type}.title", ['record' => __('notifications.record')]),
        ], self::byPortal()[$portal] ?? []);
    }
}
