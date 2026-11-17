<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\HiringRequests\Application\Contracts\HiringInterviewCalendarGatewayInterface;
use App\Modules\V1\HiringRequests\Domain\Models\HiringInterview;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewResponseEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class HiringInterviewWorkflow
{
    private const RELATIONS = [
        'item.hiringRequest.workspace',
        'item.hiringRequest.createdByMember.client.user',
        'item.talent.user',
        'proposedByAdmin.user',
        'confirmedByAdmin.user',
    ];

    public function __construct(
        private readonly HiringRuntimeSettings $settings,
        private readonly HiringEventRecorder $events,
        private readonly HiringInterviewCalendarGatewayInterface $calendar,
    ) {}

    public function propose(Admin $admin, User $user, int $itemId, string $startsAt): HiringInterview
    {
        return DB::transaction(function () use ($admin, $user, $itemId, $startsAt): HiringInterview {
            $item = $this->adminItem($admin, $itemId);
            if ($item->status !== HiringRequestItemStatusEnum::INTERVIEW
                || in_array($item->interview?->status, [HiringInterviewStatusEnum::IN_PROGRESS, HiringInterviewStatusEnum::COMPLETED], true)) {
                throw new ConflictHttpException(__('hiring.interview_proposal_not_allowed'));
            }
            $start = Carbon::parse($startsAt);
            if ($start->lte(now())) {
                throw new ConflictHttpException(__('hiring.interview_must_be_future'));
            }
            $end = $start->copy()->addMinutes($this->settings->interviewDurationMinutes());
            $this->ensureNoOverlap($item, $start, $end);

            $interview = HiringInterview::query()->where('request_item_id', $item->id)->lockForUpdate()->first();
            $previous = $interview === null ? null : $this->snapshot($interview);
            if ($interview?->google_calendar_event_id) {
                $this->calendar->deleteEvent($interview->google_calendar_event_id);
            }
            $values = [
                'status' => HiringInterviewStatusEnum::AWAITING_RESPONSES,
                'starts_at' => $start,
                'ends_at' => $end,
                'proposed_by_admin_id' => $admin->id,
                'proposed_at' => now(),
                'client_response' => HiringInterviewResponseEnum::PENDING,
                'client_response_reason' => null,
                'client_responded_at' => null,
                'talent_response' => HiringInterviewResponseEnum::PENDING,
                'talent_response_reason' => null,
                'talent_responded_at' => null,
                'confirmed_by_admin_id' => null,
                'confirmed_at' => null,
                'google_calendar_event_id' => null,
                'meeting_url' => null,
                'started_at' => null,
                'completed_at' => null,
            ];
            $interview = $interview === null
                ? $item->interview()->create($values)
                : tap($interview)->update($values);
            $item->update(['status' => HiringRequestItemStatusEnum::INTERVIEW]);
            $this->events->record($item, $user, $previous === null ? 'interview_proposed' : 'interview_rescheduled', [
                'interview_id' => $interview->id,
                'previous' => $previous,
                'starts_at' => $start,
                'ends_at' => $end,
            ]);

            return $this->load($interview);
        });
    }

    public function showForTalent(User $user, int $interviewId): HiringInterview
    {
        $talentId = $user->talent()->value('id');
        $interview = HiringInterview::query()->whereHas('item', fn (Builder $query) => $query->where('talent_id', $talentId))
            ->with(self::RELATIONS)->findOrFail($interviewId);

        return $this->synchronize($interview);
    }

    public function showForClient(User $user, Workspace $workspace, int $interviewId): HiringInterview
    {
        $interview = HiringInterview::query()->whereHas('item.hiringRequest', fn (Builder $query) => $query->where('workspace_id', $workspace->id))
            ->with(self::RELATIONS)->findOrFail($interviewId);

        return $this->synchronize($interview);
    }

    public function respondAsTalent(User $user, int $interviewId, HiringInterviewResponseEnum $response, ?string $reason): HiringInterview
    {
        $talentId = $user->talent()->value('id');

        return $this->respond($user, $interviewId, 'talent', $response, $reason, fn (Builder $query) => $query->whereHas('item', fn (Builder $item) => $item->where('talent_id', $talentId)));
    }

    public function respondAsClient(User $user, Workspace $workspace, int $interviewId, HiringInterviewResponseEnum $response, ?string $reason): HiringInterview
    {
        return $this->respond($user, $interviewId, 'client', $response, $reason, fn (Builder $query) => $query->whereHas('item.hiringRequest', fn (Builder $request) => $request->where('workspace_id', $workspace->id)));
    }

    public function respondOnBehalf(
        Admin $admin,
        User $user,
        int $itemId,
        string $party,
        HiringInterviewResponseEnum $response,
        ?string $reason,
    ): HiringInterview {
        return $this->respond(
            $user,
            $itemId,
            $party,
            $response,
            $reason,
            fn (Builder $query) => $query
                ->where('request_item_id', $itemId)
                ->whereHas('item.hiringRequest', fn (Builder $request) => $request->where('assigned_admin_id', $admin->id)),
            true,
        );
    }

    public function confirm(Admin $admin, User $user, int $itemId, ?string $manualMeetingUrl): HiringInterview
    {
        return DB::transaction(function () use ($admin, $user, $itemId, $manualMeetingUrl): HiringInterview {
            $item = $this->adminItem($admin, $itemId);
            $interview = $item->interview()->lockForUpdate()->firstOrFail();
            if ($interview->status !== HiringInterviewStatusEnum::AWAITING_ADMIN_CONFIRMATION) {
                throw new ConflictHttpException(__('hiring.interview_confirmation_not_allowed'));
            }
            $this->ensureNoOverlap($item, $interview->starts_at, $interview->ends_at, $interview->id);
            $eventId = null;
            $meetingUrl = $manualMeetingUrl;
            if (blank($meetingUrl)) {
                $item->loadMissing(['talent.user', 'hiringRequest.createdByMember.client.user']);
                $meeting = $this->calendar->createOnlineMeeting(
                    $interview,
                    'Qader Hiring Interview - '.$item->reference,
                    array_filter([
                        $item->talent->user?->email,
                        $item->hiringRequest->createdByMember?->client?->user?->email,
                    ]),
                );
                $eventId = $meeting->eventId;
                $meetingUrl = $meeting->meetingUrl;
            }
            $interview->update([
                'status' => HiringInterviewStatusEnum::CONFIRMED,
                'confirmed_by_admin_id' => $admin->id,
                'confirmed_at' => now(),
                'google_calendar_event_id' => $eventId,
                'meeting_url' => $meetingUrl,
            ]);
            $item->update(['status' => HiringRequestItemStatusEnum::INTERVIEW]);
            $this->events->record($item, $user, 'interview_confirmed', [
                'interview_id' => $interview->id,
                'starts_at' => $interview->starts_at,
                'ends_at' => $interview->ends_at,
                'calendar_managed' => $eventId !== null,
            ]);

            return $this->load($interview);
        });
    }

    public function start(Admin $admin, User $user, int $itemId): HiringInterview
    {
        return DB::transaction(function () use ($admin, $user, $itemId): HiringInterview {
            $item = $this->adminItem($admin, $itemId);
            $interview = $item->interview()->lockForUpdate()->firstOrFail();
            if ($interview->status === HiringInterviewStatusEnum::IN_PROGRESS) {
                return $this->load($interview);
            }
            if ($interview->status !== HiringInterviewStatusEnum::CONFIRMED || now()->lt($interview->starts_at) || now()->gte($interview->ends_at)) {
                throw new ConflictHttpException(__('hiring.interview_start_not_allowed'));
            }
            $interview->update(['status' => HiringInterviewStatusEnum::IN_PROGRESS, 'started_at' => now()]);
            $this->events->record($item, $user, 'interview_started', ['interview_id' => $interview->id, 'source' => 'manual']);

            return $this->load($interview);
        });
    }

    public function complete(Admin $admin, User $user, int $itemId): HiringInterview
    {
        return DB::transaction(function () use ($admin, $user, $itemId): HiringInterview {
            $item = $this->adminItem($admin, $itemId);
            $interview = $item->interview()->lockForUpdate()->firstOrFail();
            if ($interview->status === HiringInterviewStatusEnum::COMPLETED) {
                return $this->load($interview);
            }
            if ($interview->status !== HiringInterviewStatusEnum::IN_PROGRESS) {
                throw new ConflictHttpException(__('hiring.interview_complete_not_allowed'));
            }
            $this->completeLocked($interview, $item, $user, 'manual');

            return $this->load($interview);
        });
    }

    public function synchronizeDue(): int
    {
        $count = 0;
        HiringInterview::query()->whereIn('status', [
            HiringInterviewStatusEnum::CONFIRMED->value,
            HiringInterviewStatusEnum::IN_PROGRESS->value,
        ])->where('starts_at', '<=', now())->orderBy('id')->pluck('id')->each(function (int $id) use (&$count): void {
            DB::transaction(function () use ($id, &$count): void {
                $interview = HiringInterview::query()->with('item.hiringRequest')->lockForUpdate()->find($id);
                if ($interview === null) {
                    return;
                }
                $item = $interview->item;
                if ($interview->status === HiringInterviewStatusEnum::CONFIRMED && now()->gte($interview->starts_at) && now()->lt($interview->ends_at)) {
                    $interview->update(['status' => HiringInterviewStatusEnum::IN_PROGRESS, 'started_at' => $interview->started_at ?? $interview->starts_at]);
                    $this->events->record($item, null, 'interview_started', ['interview_id' => $interview->id, 'source' => 'automatic']);
                    $count++;
                }
                if (in_array($interview->status, [HiringInterviewStatusEnum::CONFIRMED, HiringInterviewStatusEnum::IN_PROGRESS], true) && now()->gte($interview->ends_at)) {
                    if ($interview->status === HiringInterviewStatusEnum::CONFIRMED) {
                        $interview->update(['status' => HiringInterviewStatusEnum::IN_PROGRESS, 'started_at' => $interview->starts_at]);
                    }
                    $this->completeLocked($interview, $item, null, 'automatic');
                    $count++;
                }
            });
        });

        return $count;
    }

    public function cancelForItem(HiringRequestItem $item, ?User $actor, string $action): void
    {
        $interview = $item->interview()->lockForUpdate()->first();
        if ($interview === null || in_array($interview->status, [HiringInterviewStatusEnum::CANCELLED, HiringInterviewStatusEnum::COMPLETED], true)) {
            return;
        }
        if ($interview->google_calendar_event_id) {
            $this->calendar->deleteEvent($interview->google_calendar_event_id);
        }
        $interview->update([
            'status' => HiringInterviewStatusEnum::CANCELLED,
            'google_calendar_event_id' => null,
        ]);
        $this->events->record($item, $actor, $action, ['interview_id' => $interview->id]);
    }

    private function respond(
        User $user,
        int $subjectId,
        string $party,
        HiringInterviewResponseEnum $response,
        ?string $reason,
        callable $scope,
        bool $subjectIsItem = false,
    ): HiringInterview
    {
        return DB::transaction(function () use ($user, $subjectId, $party, $response, $reason, $scope, $subjectIsItem): HiringInterview {
            $query = HiringInterview::query();
            $scope($query);
            $query->with('item.hiringRequest')->lockForUpdate();
            $interview = $subjectIsItem
                ? $query->where('request_item_id', $subjectId)->firstOrFail()
                : $query->findOrFail($subjectId);
            $initialResponse = $interview->status === HiringInterviewStatusEnum::AWAITING_RESPONSES
                && $interview->{$party.'_response'} === HiringInterviewResponseEnum::PENDING;
            $confirmedCancellation = $interview->status === HiringInterviewStatusEnum::CONFIRMED
                && $response !== HiringInterviewResponseEnum::ACCEPTED
                && now()->lt($interview->starts_at);
            if (! $initialResponse && ! $confirmedCancellation) {
                throw new ConflictHttpException(__('hiring.interview_response_not_allowed'));
            }
            if ($confirmedCancellation && $interview->google_calendar_event_id) {
                $this->calendar->deleteEvent($interview->google_calendar_event_id);
            }
            $interview->update([
                $party.'_response' => $response,
                $party.'_response_reason' => $reason,
                $party.'_responded_at' => now(),
                'google_calendar_event_id' => $confirmedCancellation ? null : $interview->google_calendar_event_id,
                'meeting_url' => $confirmedCancellation ? null : $interview->meeting_url,
            ]);
            $interview->refresh();
            if ($response !== HiringInterviewResponseEnum::ACCEPTED) {
                $interview->update(['status' => HiringInterviewStatusEnum::AWAITING_RESCHEDULE]);
                $interview->item->update(['status' => HiringRequestItemStatusEnum::INTERVIEW]);
            } elseif (
                $interview->client_response === HiringInterviewResponseEnum::ACCEPTED
                && $interview->talent_response === HiringInterviewResponseEnum::ACCEPTED
            ) {
                $interview->update(['status' => HiringInterviewStatusEnum::AWAITING_ADMIN_CONFIRMATION]);
            }
            $this->events->record($interview->item, $user, 'interview_schedule_responded', [
                'interview_id' => $interview->id,
                'party' => $party,
                'response' => $response->value,
                'reason' => $reason,
            ]);

            return $this->load($interview);
        });
    }

    private function synchronize(HiringInterview $interview): HiringInterview
    {
        if (in_array($interview->status, [HiringInterviewStatusEnum::CONFIRMED, HiringInterviewStatusEnum::IN_PROGRESS], true)) {
            $this->synchronizeDue();

            return $this->load($interview);
        }

        return $interview;
    }

    private function completeLocked(HiringInterview $interview, HiringRequestItem $item, ?User $actor, string $source): void
    {
        $completedAt = now();
        $interview->update(['status' => HiringInterviewStatusEnum::COMPLETED, 'completed_at' => $completedAt]);
        $item->update([
            'status' => HiringRequestItemStatusEnum::INTERVIEW,
            'decision_due_at' => $completedAt->copy()->addHours($this->settings->decisionResponseHours()),
        ]);
        $this->events->record($item, $actor, 'interview_completed', [
            'interview_id' => $interview->id,
            'source' => $source,
            'decision_due_at' => $item->decision_due_at,
        ]);
    }

    private function adminItem(Admin $admin, int $itemId): HiringRequestItem
    {
        return HiringRequestItem::query()
            ->whereHas('hiringRequest', fn (Builder $query) => $query->where('assigned_admin_id', $admin->id))
            ->with(['hiringRequest.createdByMember.client.user', 'hiringRequest.workspace', 'talent.user', 'interview'])
            ->lockForUpdate()->findOrFail($itemId);
    }

    private function ensureNoOverlap(HiringRequestItem $item, Carbon $start, Carbon $end, ?int $exceptId = null): void
    {
        $active = [HiringInterviewStatusEnum::CONFIRMED->value, HiringInterviewStatusEnum::IN_PROGRESS->value];
        $query = HiringInterview::query()->whereIn('status', $active)
            ->where('starts_at', '<', $end)->where('ends_at', '>', $start)
            ->where(function (Builder $query) use ($item): void {
                $query->whereHas('item', fn (Builder $items) => $items->where('talent_id', $item->talent_id))
                    ->orWhereHas('item.hiringRequest', fn (Builder $requests) => $requests->where('created_by_member_id', $item->hiringRequest->created_by_member_id));
            });
        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }
        if ($query->exists()) {
            throw new ConflictHttpException(__('hiring.interview_time_conflict'));
        }
    }

    private function snapshot(HiringInterview $interview): array
    {
        return [
            'starts_at' => $interview->starts_at,
            'ends_at' => $interview->ends_at,
            'status' => $interview->status->value,
            'client_response' => $interview->client_response->value,
            'client_response_reason' => $interview->client_response_reason,
            'talent_response' => $interview->talent_response->value,
            'talent_response_reason' => $interview->talent_response_reason,
            'google_calendar_event_id' => $interview->google_calendar_event_id,
        ];
    }

    private function load(HiringInterview $interview): HiringInterview
    {
        return $interview->refresh()->load(self::RELATIONS);
    }
}
