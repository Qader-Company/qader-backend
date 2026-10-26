<?php

namespace App\Modules\V1\TalentApplications\Application\Services;

use App\Modules\V1\TalentApplications\Domain\Models\Interview;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplicationEvent;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use Illuminate\Support\Facades\DB;

class InterviewStateSynchronizer
{
    public function synchronize(Interview $interview): Interview
    {
        return DB::transaction(function () use ($interview): Interview {
            $locked = Interview::query()
                ->with(['application', 'selectedSlot'])
                ->lockForUpdate()
                ->findOrFail($interview->id);

            return $this->synchronizeLocked($locked);
        });
    }

    public function synchronizeDue(): int
    {
        $count = 0;

        Interview::query()
            ->whereIn('status', [
                InterviewStatusEnum::CONFIRMED->value,
                InterviewStatusEnum::IN_PROGRESS->value,
            ])
            ->whereHas('selectedSlot', fn ($query) => $query->where('starts_at', '<=', now()))
            ->orderBy('id')
            ->chunkById(100, function ($interviews) use (&$count): void {
                foreach ($interviews as $interview) {
                    $before = $interview->status;
                    $after = $this->synchronize($interview)->status;
                    $count += $before !== $after ? 1 : 0;
                }
            });

        return $count;
    }

    public function synchronizeLocked(Interview $interview): Interview
    {
        $slot = $interview->relationLoaded('selectedSlot')
            ? $interview->selectedSlot
            : $interview->selectedSlot()->first();

        if ($slot === null) {
            return $interview;
        }

        $now = now();
        if ($interview->status === InterviewStatusEnum::CONFIRMED && $now->greaterThanOrEqualTo($slot->starts_at)) {
            $interview->update([
                'status' => InterviewStatusEnum::IN_PROGRESS,
                'started_at' => $slot->starts_at,
            ]);
            $this->event($interview, 'interview_started');
        }

        if ($interview->status === InterviewStatusEnum::IN_PROGRESS && $now->greaterThanOrEqualTo($slot->ends_at)) {
            $interview->update([
                'status' => InterviewStatusEnum::COMPLETED,
                'completed_at' => $slot->ends_at,
            ]);
            $this->event($interview, 'interview_completed');
        }

        return $interview->refresh()->load([
            'application.talent.user',
            'selectedSlot',
            'slots',
            'admins.user',
            'evaluation.evaluator.user',
        ]);
    }

    private function event(Interview $interview, string $action): void
    {
        TalentApplicationEvent::query()->create([
            'application_id' => $interview->application_id,
            'admin_id' => $interview->application->assigned_admin_id,
            'action' => $action,
            'data' => [
                'interview_id' => $interview->id,
                'round_number' => $interview->round_number,
                'transition_source' => 'automatic',
            ],
            'created_at' => now(),
        ]);
    }
}
