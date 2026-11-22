<?php

namespace App\Modules\V1\Notifications\Application\Services;

use App\Modules\V1\HiringRequests\Domain\Models\HiringInterview;
use App\Modules\V1\HiringRequests\Domain\Models\HiringOffer;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferPartyEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\Notifications\Application\Handlers\HiringNotificationHandler;
use App\Modules\V1\TalentApplications\Domain\Models\InterviewSlot;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewSlotStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Runs every minute: one reminder an hour before each appointment/deadline and one when an interview starts.
 * Each reminder is claimed once in `notification_reminders`; the key contains the scheduled time,
 * so a rescheduled interview gets fresh reminders and the old ones never fire.
 */
class NotificationReminderService
{
    private const ITEM_RELATIONS = ['hiringRequest.workspace', 'talent', 'jobTitle'];

    public function __construct(
        private readonly Notifier $notifier,
        private readonly NotificationRecipients $recipients,
        private readonly HiringNotificationHandler $hiring,
    ) {}

    public function sendDue(): int
    {
        return $this->hiringInterviews() + $this->talentInterviews() + $this->offerDeadlines() + $this->decisionDeadlines();
    }

    private function hiringInterviews(): int
    {
        $sent = 0;
        foreach ($this->windows() as $kind => [$from, $to, $statuses]) {
            $interviews = HiringInterview::query()
                ->whereIn('status', array_map(fn ($status) => $status->value, $statuses === 'upcoming'
                    ? [HiringInterviewStatusEnum::CONFIRMED]
                    : [HiringInterviewStatusEnum::CONFIRMED, HiringInterviewStatusEnum::IN_PROGRESS]))
                ->whereBetween('starts_at', [$from, $to])
                ->with(array_map(fn (string $relation): string => "item.{$relation}", self::ITEM_RELATIONS))
                ->get();

            foreach ($interviews as $interview) {
                $item = $interview->item;
                if ($item === null || ! $this->claim("hiring_interview:{$interview->id}:{$kind}:{$interview->starts_at->timestamp}")) {
                    continue;
                }
                $type = $kind === 'before' ? 'hiring_interview_reminder' : 'hiring_interview_starting';
                $this->sendToHiringParties($item, $type, ['talent', 'client', 'admin']);
                $sent++;
            }
        }

        return $sent;
    }

    private function talentInterviews(): int
    {
        $sent = 0;
        foreach ($this->windows() as $kind => [$from, $to, $statuses]) {
            $slots = InterviewSlot::query()
                ->where('status', InterviewSlotStatusEnum::SELECTED->value)
                ->whereBetween('starts_at', [$from, $to])
                ->whereHas('interview', fn ($query) => $query->whereIn('status', $statuses === 'upcoming'
                    ? [InterviewStatusEnum::CONFIRMED->value]
                    : [InterviewStatusEnum::CONFIRMED->value, InterviewStatusEnum::IN_PROGRESS->value]))
                ->with(['interview.application.talent', 'interview.admins'])
                ->get();

            foreach ($slots as $slot) {
                $application = $slot->interview?->application;
                if ($application === null || ! $this->claim("talent_interview:{$slot->interview_id}:{$kind}:{$slot->starts_at->timestamp}")) {
                    continue;
                }
                $suffix = $kind === 'before' ? 'reminder' : 'starting';
                $this->notifier->send([$application->talent?->user_id], "talent_interview_{$suffix}", [], [], $application->id);
                $this->notifier->send(
                    [$this->recipients->admin($application->assigned_admin_id), ...$slot->interview->admins->pluck('user_id')->all()],
                    "talent_interview_{$suffix}", ['talent_code' => $application->talent?->talent_code], [],
                    $application->id,
                );
                $sent++;
            }
        }

        return $sent;
    }

    private function offerDeadlines(): int
    {
        $offers = HiringOffer::query()
            ->where('status', HiringOfferStatusEnum::PENDING->value)
            ->whereNotNull('sent_at')
            ->whereBetween('response_due_at', [now(), $this->beforeLimit()])
            ->whereHas('item', fn ($query) => $query->where('status', HiringRequestItemStatusEnum::OFFER->value))
            ->with(array_map(fn (string $relation): string => "item.{$relation}", self::ITEM_RELATIONS))
            ->get();

        $sent = 0;
        foreach ($offers as $offer) {
            if (! $this->claim("hiring_offer:{$offer->id}:before:{$offer->response_due_at->timestamp}")) {
                continue;
            }
            // The party that must answer is the one that did not propose the offer.
            $party = $offer->proposed_by === HiringOfferPartyEnum::TALENT ? 'client' : 'talent';
            $this->sendToHiringParties($offer->item, 'hiring_offer_deadline_reminder', [$party]);
            $sent++;
        }

        return $sent;
    }

    private function decisionDeadlines(): int
    {
        $items = HiringRequestItem::query()
            ->where('status', HiringRequestItemStatusEnum::INTERVIEW->value)
            ->whereNull('client_decision')
            ->whereBetween('decision_due_at', [now(), $this->beforeLimit()])
            ->with(self::ITEM_RELATIONS)
            ->get();

        $sent = 0;
        foreach ($items as $item) {
            if (! $this->claim("hiring_decision:{$item->id}:before:{$item->decision_due_at->timestamp}")) {
                continue;
            }
            $this->sendToHiringParties($item, 'hiring_decision_deadline_reminder', ['client']);
            $sent++;
        }

        return $sent;
    }

    private function sendToHiringParties(HiringRequestItem $item, string $type, array $parties): void
    {
        $params = $this->hiring->params($item->hiringRequest, $item);
        foreach ($parties as $party) {
            $recipient = $this->recipients->hiringParty($party, $item->hiringRequest, $item);
            $this->notifier->send($recipient['ids'], $type, $params, [], $recipient['target_id'], $recipient['workspace_id']);
        }
    }

    /** @return array<string, array{0: CarbonInterface, 1: CarbonInterface, 2: string}> */
    private function windows(): array
    {
        $grace = (int) config('notifications.reminders.start_grace_minutes', 10);

        return [
            'before' => [now(), $this->beforeLimit(), 'upcoming'],
            'start' => [now()->subMinutes($grace), now(), 'started'],
        ];
    }

    private function beforeLimit(): CarbonInterface
    {
        return now()->addMinutes((int) config('notifications.reminders.before_minutes', 60));
    }

    private function claim(string $key): bool
    {
        return DB::table('notification_reminders')->insertOrIgnore(['key' => $key, 'sent_at' => now()]) === 1;
    }
}
