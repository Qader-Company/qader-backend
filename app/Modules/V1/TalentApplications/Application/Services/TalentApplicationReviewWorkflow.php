<?php

namespace App\Modules\V1\TalentApplications\Application\Services;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\TalentApplications\Domain\Exceptions\InterviewScheduleConflictException;
use App\Modules\V1\TalentApplications\Domain\Models\Interview;
use App\Modules\V1\TalentApplications\Domain\Models\InterviewSlot;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplicationEvent;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplicationReviewerAssignment;
use App\Modules\V1\TalentApplications\Domain\Models\TalentBlacklistEntry;
use App\Modules\V1\TalentApplications\Domain\Models\TalentEvaluation;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewSlotStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\ReapplyPolicyEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Users\Application\Services\UserAccessResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TalentApplicationReviewWorkflow
{
    /** @param array<int, int> $applicationIds */
    public function assignReviewer(array $applicationIds, int $reviewerAdminId, Admin $actor): void
    {
        DB::transaction(function () use ($applicationIds, $reviewerAdminId, $actor): void {
            $reviewer = $this->lockAndValidateReviewer($reviewerAdminId);
            $ids = collect($applicationIds)->map(static fn ($id): int => (int) $id)->unique()->sort()->values();
            $applications = TalentApplication::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($applications->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'application_ids' => [__('talents.review.invalid_applications')],
                ]);
            }

            foreach ($applications as $application) {
                $this->ensureReviewIsOpen($application);
            }

            foreach ($applications as $application) {
                $this->assignLockedApplication($application, $reviewer, $actor);
            }
        });
    }

    public function startReview(int $applicationId, Admin $actor): TalentApplication
    {
        return DB::transaction(function () use ($applicationId, $actor): TalentApplication {
            $application = TalentApplication::query()->lockForUpdate()->findOrFail($applicationId);
            $this->ensureReviewIsOpen($application);

            if ($application->assigned_admin_id !== null && $application->assigned_admin_id !== $actor->id) {
                throw new ConflictHttpException(__('talents.review.assigned_to_another_reviewer'));
            }

            if ($application->assigned_admin_id === null) {
                $this->assignLockedApplication($application, $actor, $actor);
                $application->refresh();
            }

            if ($this->status($application) === TalentApplicationStatusEnum::SUBMITTED) {
                $application->update(['status' => TalentApplicationStatusEnum::UNDER_REVIEW]);
                $this->recordEvent($application, $actor, 'review_started');
            }

            return $application->refresh();
        });
    }

    public function saveEvaluation(int $applicationId, array $data, Admin $actor): TalentEvaluation
    {
        return DB::transaction(function () use ($applicationId, $data, $actor): TalentEvaluation {
            $application = TalentApplication::query()->lockForUpdate()->findOrFail($applicationId);
            $this->ensureCurrentReviewer($application, $actor);

            if ($this->status($application) !== TalentApplicationStatusEnum::UNDER_REVIEW) {
                throw new ConflictHttpException(__('talents.review.evaluation_not_editable'));
            }

            $evaluation = TalentEvaluation::query()->updateOrCreate(
                ['application_id' => $application->id],
                [
                    'evaluator_admin_id' => $actor->id,
                    ...Arr::only($data, [
                        'technical_skills_rating',
                        'communication_skills_rating',
                        'problem_solving_rating',
                        'soft_skills_rating',
                    ]),
                    'recommended_salary_min' => $data['recommended_salary_min'] ?? null,
                    'recommended_salary_max' => $data['recommended_salary_max'] ?? null,
                    'currency_id' => $data['currency_id'] ?? null,
                    'hr_report' => $data['hr_report'] ?? null,
                    'technical_report' => $data['technical_report'] ?? null,
                    'psychology_report' => $data['psychology_report'] ?? null,
                ],
            );

            $evaluation->tips()->delete();
            foreach ($data['tips'] ?? [] as $index => $tip) {
                $evaluation->tips()->create([
                    ...$tip,
                    'display_order' => $index,
                ]);
            }

            $this->recordEvent($application, $actor, 'evaluation_saved');

            return $evaluation->load(['tips', 'currency.translations', 'evaluator.user']);
        });
    }

    public function reject(int $applicationId, array $data, Admin $actor): TalentApplication
    {
        return DB::transaction(function () use ($applicationId, $data, $actor): TalentApplication {
            $application = TalentApplication::query()->lockForUpdate()->findOrFail($applicationId);
            $this->ensureDecisionAllowed($application, $actor);
            $decidedAt = now();
            $policy = ReapplyPolicyEnum::from($data['reapply_policy']);
            $reapplyAt = $policy === ReapplyPolicyEnum::ALLOWED_AFTER_PERIOD
                ? $decidedAt->copy()->addMonthsNoOverflow((int) $data['reapply_after_months'])
                : null;

            $application->update([
                'status' => TalentApplicationStatusEnum::REJECTED,
                'review_decided_by_admin_id' => $actor->id,
                'review_decided_at' => $decidedAt,
                'reapply_at' => $reapplyAt,
            ]);

            if ($policy === ReapplyPolicyEnum::BLACKLISTED) {
                TalentBlacklistEntry::query()->create([
                    'talent_id' => $application->talent_id,
                    'application_id' => $application->id,
                    'blacklist_reason' => $data['blacklist_reason'],
                    'blocked_by_admin_id' => $actor->id,
                    'blocked_at' => $decidedAt,
                ]);
            }

            $this->recordEvent($application, $actor, 'application_rejected', [
                'reapply_policy' => $policy->value,
                'reapply_at' => $reapplyAt?->toISOString(),
            ]);

            return $application->refresh();
        });
    }

    public function scheduleInterview(int $applicationId, array $data, Admin $actor): Interview
    {
        return DB::transaction(function () use ($applicationId, $data, $actor): Interview {
            $application = TalentApplication::query()->lockForUpdate()->findOrFail($applicationId);
            $this->ensureDecisionAllowed($application, $actor);
            $interviewerIds = collect($data['interviewer_admin_ids'])
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $interviewers = Admin::query()
                ->with('user')
                ->whereIn('id', $interviewerIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if (
                $interviewers->count() !== $interviewerIds->count()
                || $interviewers->contains(fn (Admin $admin): bool => ! (bool) $admin->user?->is_active)
            ) {
                throw ValidationException::withMessages([
                    'interviewer_admin_ids' => [__('talents.review.invalid_interviewers')],
                ]);
            }

            $slots = collect($data['slots'])->map(function (array $slot): array {
                $start = CarbonImmutable::parse($slot['start'])->utc();

                return [
                    'mode' => $slot['mode'],
                    'starts_at' => $start,
                    'ends_at' => $start->addMinutes(60),
                    'location' => $slot['mode'] === 'offline' ? ($slot['location'] ?? null) : null,
                    'latitude' => $slot['mode'] === 'offline' ? ($slot['latitude'] ?? null) : null,
                    'longitude' => $slot['mode'] === 'offline' ? ($slot['longitude'] ?? null) : null,
                    'status' => InterviewSlotStatusEnum::AVAILABLE->value,
                ];
            });

            if ($slots->pluck('starts_at')->map->getTimestamp()->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'slots' => [__('talents.review.duplicate_slots')],
                ]);
            }

            $conflicts = collect();
            foreach ($slots as $slot) {
                $matches = InterviewSlot::query()
                    ->whereIn('status', InterviewSlotStatusEnum::blockingValues())
                    ->where('starts_at', '<', $slot['ends_at'])
                    ->where('ends_at', '>', $slot['starts_at'])
                    ->whereHas('interview.admins', fn ($query) => $query->whereIn('admins.id', $interviewerIds))
                    ->with('interview.admins:id')
                    ->get();

                foreach ($matches as $match) {
                    $conflictingAdminIds = $match->interview->admins
                        ->pluck('id')
                        ->intersect($interviewerIds)
                        ->values()
                        ->all();
                    $conflicts->push([
                        'slot_id' => $match->id,
                        'start' => $match->starts_at->toISOString(),
                        'end' => $match->ends_at->toISOString(),
                        'interviewer_admin_ids' => $conflictingAdminIds,
                    ]);
                }
            }

            if ($conflicts->isNotEmpty()) {
                throw new InterviewScheduleConflictException(
                    $conflicts->unique('slot_id')->values()->all(),
                    __('talents.review.interviewer_time_conflict'),
                );
            }

            $interview = Interview::query()->create([
                'application_id' => $application->id,
                'type' => $data['type'],
                'status' => InterviewStatusEnum::AWAITING_SLOT_SELECTION,
                'created_by_admin_id' => $actor->id,
            ]);
            $interview->admins()->attach($interviewerIds);
            $interview->slots()->createMany($slots->all());

            $application->update([
                'status' => TalentApplicationStatusEnum::INTERVIEW_REQUIRED,
                'review_decided_by_admin_id' => $actor->id,
                'review_decided_at' => now(),
            ]);
            $this->recordEvent($application, $actor, 'interview_scheduled', [
                'interview_id' => $interview->id,
            ]);

            return $interview->load(['admins.user', 'slots']);
        });
    }

    public function replaceInterviewSlots(int $applicationId, array $data, Admin $actor): Interview
    {
        return DB::transaction(function () use ($applicationId, $data, $actor): Interview {
            $application = TalentApplication::query()->lockForUpdate()->findOrFail($applicationId);
            $this->ensureCurrentReviewer($application, $actor);

            if ($this->status($application) !== TalentApplicationStatusEnum::INTERVIEW_REQUIRED) {
                throw new ConflictHttpException(__('talents.review.replacement_slots_not_allowed'));
            }

            $interview = Interview::query()
                ->where('application_id', $application->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($interview->status !== InterviewStatusEnum::AWAITING_NEW_SLOTS) {
                throw new ConflictHttpException(__('talents.review.replacement_slots_not_allowed'));
            }

            $interviewerIds = $interview->admins()->pluck('admins.id')->sort()->values();
            Admin::query()->whereIn('id', $interviewerIds)->orderBy('id')->lockForUpdate()->get();

            $slots = collect($data['slots'])->map(function (array $slot): array {
                $start = CarbonImmutable::parse($slot['start'])->utc();

                return [
                    'mode' => $slot['mode'],
                    'starts_at' => $start,
                    'ends_at' => $start->addMinutes(60),
                    'location' => $slot['mode'] === 'offline' ? ($slot['location'] ?? null) : null,
                    'latitude' => $slot['mode'] === 'offline' ? ($slot['latitude'] ?? null) : null,
                    'longitude' => $slot['mode'] === 'offline' ? ($slot['longitude'] ?? null) : null,
                    'status' => InterviewSlotStatusEnum::AVAILABLE->value,
                ];
            });

            if ($slots->pluck('starts_at')->map->getTimestamp()->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'slots' => [__('talents.review.duplicate_slots')],
                ]);
            }

            $conflicts = collect();
            foreach ($slots as $slot) {
                $matches = InterviewSlot::query()
                    ->whereIn('status', InterviewSlotStatusEnum::blockingValues())
                    ->where('starts_at', '<', $slot['ends_at'])
                    ->where('ends_at', '>', $slot['starts_at'])
                    ->whereHas('interview.admins', fn ($query) => $query->whereIn('admins.id', $interviewerIds))
                    ->with('interview.admins:id')
                    ->get();

                foreach ($matches as $match) {
                    $conflicts->push([
                        'slot_id' => $match->id,
                        'start' => $match->starts_at->toISOString(),
                        'end' => $match->ends_at->toISOString(),
                        'interviewer_admin_ids' => $match->interview->admins
                            ->pluck('id')
                            ->intersect($interviewerIds)
                            ->values()
                            ->all(),
                    ]);
                }
            }

            if ($conflicts->isNotEmpty()) {
                throw new InterviewScheduleConflictException(
                    $conflicts->unique('slot_id')->values()->all(),
                    __('talents.review.interviewer_time_conflict'),
                );
            }

            $interview->slots()->createMany($slots->all());
            $interview->update([
                'status' => InterviewStatusEnum::AWAITING_SLOT_SELECTION,
                'rejected_slots_reason' => null,
            ]);
            $this->recordEvent($application, $actor, 'replacement_interview_slots_offered', [
                'interview_id' => $interview->id,
                'slots_count' => $slots->count(),
            ]);

            return $interview->refresh()->load(['admins.user', 'slots']);
        });
    }

    private function lockAndValidateReviewer(int $reviewerAdminId): Admin
    {
        $reviewer = Admin::query()->with('user')->lockForUpdate()->find($reviewerAdminId);
        $valid = $reviewer !== null
            && (bool) $reviewer->user?->is_active
            && UserAccessResolver::hasAnyPermission(
                $reviewer->user,
                [AdminPermissionEnum::TALENT_APPLICATIONS_REVIEW->value],
            );

        if (! $valid) {
            throw ValidationException::withMessages([
                'reviewer_admin_id' => [__('talents.review.invalid_reviewer')],
            ]);
        }

        return $reviewer;
    }

    private function assignLockedApplication(TalentApplication $application, Admin $reviewer, Admin $actor): void
    {
        if ($application->assigned_admin_id === $reviewer->id) {
            return;
        }

        $wasAssigned = $application->assigned_admin_id !== null;
        $assignedAt = now();
        $application->reviewerAssignments()->whereNull('ended_at')->update(['ended_at' => $assignedAt]);
        TalentApplicationReviewerAssignment::query()->create([
            'application_id' => $application->id,
            'reviewer_admin_id' => $reviewer->id,
            'assigned_by_admin_id' => $actor->id,
            'assigned_at' => $assignedAt,
        ]);
        $application->update(['assigned_admin_id' => $reviewer->id]);
        $this->recordEvent(
            $application,
            $actor,
            $wasAssigned ? 'reviewer_reassigned' : 'reviewer_assigned',
            ['reviewer_admin_id' => $reviewer->id],
        );
    }

    private function ensureReviewIsOpen(TalentApplication $application): void
    {
        if (! in_array($this->status($application), [
            TalentApplicationStatusEnum::SUBMITTED,
            TalentApplicationStatusEnum::UNDER_REVIEW,
        ], true)) {
            throw new ConflictHttpException(__('talents.review.application_closed'));
        }
    }

    private function ensureCurrentReviewer(TalentApplication $application, Admin $actor): void
    {
        if ($application->assigned_admin_id !== $actor->id) {
            throw new ConflictHttpException(__('talents.review.current_reviewer_required'));
        }
    }

    private function ensureDecisionAllowed(TalentApplication $application, Admin $actor): void
    {
        if ($this->status($application) !== TalentApplicationStatusEnum::UNDER_REVIEW) {
            throw new ConflictHttpException(__('talents.review.decision_not_allowed'));
        }

        $this->ensureCurrentReviewer($application, $actor);

        if (! $application->evaluation()->exists()) {
            throw new ConflictHttpException(__('talents.review.evaluation_required'));
        }

        if ($application->interview()->exists()) {
            throw new ConflictHttpException(__('talents.review.decision_already_recorded'));
        }
    }

    private function status(TalentApplication $application): TalentApplicationStatusEnum
    {
        return $application->status instanceof TalentApplicationStatusEnum
            ? $application->status
            : TalentApplicationStatusEnum::from($application->status);
    }

    private function recordEvent(
        TalentApplication $application,
        Admin $actor,
        string $action,
        ?array $data = null,
    ): void {
        TalentApplicationEvent::query()->create([
            'application_id' => $application->id,
            'admin_id' => $actor->id,
            'action' => $action,
            'data' => $data,
            'created_at' => now(),
        ]);
    }
}
