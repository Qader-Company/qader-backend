<?php

namespace App\Modules\V1\TalentApplications\Application\Services;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\TalentApplications\Domain\Models\Interview;
use App\Modules\V1\TalentApplications\Domain\Models\InterviewEvaluation;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplicationEvent;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AdminInterviewWorkflow
{
    public function __construct(private readonly InterviewStateSynchronizer $synchronizer) {}

    public function start(int $interviewId, Admin $actor): Interview
    {
        return DB::transaction(function () use ($interviewId, $actor): Interview {
            $interview = Interview::query()->with('selectedSlot')->lockForUpdate()->findOrFail($interviewId);
            $interview = $this->synchronizer->synchronizeLocked($interview);
            $this->ensureResponsibleAdmin($interview->application, $actor);
            if ($interview->status === InterviewStatusEnum::IN_PROGRESS) {
                return $interview;
            }
            $slot = $interview->selectedSlot;
            if (
                $interview->status !== InterviewStatusEnum::CONFIRMED
                || $slot === null
                || now()->lt($slot->starts_at->copy()->subMinutes(15))
                || now()->gte($slot->ends_at)
            ) {
                throw new ConflictHttpException(__('talents.interview.start_not_allowed'));
            }
            $interview->update(['status' => InterviewStatusEnum::IN_PROGRESS, 'started_at' => now()]);
            $this->event($interview, $actor, 'interview_started', 'manual');

            return $this->load($interview);
        });
    }

    public function complete(int $interviewId, Admin $actor): Interview
    {
        return DB::transaction(function () use ($interviewId, $actor): Interview {
            $interview = Interview::query()->with('selectedSlot')->lockForUpdate()->findOrFail($interviewId);
            $interview = $this->synchronizer->synchronizeLocked($interview);
            $this->ensureResponsibleAdmin($interview->application, $actor);
            if ($interview->status === InterviewStatusEnum::COMPLETED) {
                return $interview;
            }
            if ($interview->status !== InterviewStatusEnum::IN_PROGRESS) {
                throw new ConflictHttpException(__('talents.interview.complete_not_allowed'));
            }
            $interview->update(['status' => InterviewStatusEnum::COMPLETED, 'completed_at' => now()]);
            $this->event($interview, $actor, 'interview_completed', 'manual');

            return $this->load($interview);
        });
    }

    public function saveEvaluation(int $interviewId, array $data, Admin $actor): InterviewEvaluation
    {
        return DB::transaction(function () use ($interviewId, $data, $actor): InterviewEvaluation {
            $interview = Interview::query()->with('selectedSlot')->lockForUpdate()->findOrFail($interviewId);
            $interview = $this->synchronizer->synchronizeLocked($interview);
            $application = TalentApplication::query()->lockForUpdate()->findOrFail($interview->application_id);
            $this->ensureResponsibleAdmin($application, $actor);
            if (
                $interview->status !== InterviewStatusEnum::COMPLETED
                || $application->status !== TalentApplicationStatusEnum::INTERVIEW_REQUIRED
            ) {
                throw new ConflictHttpException(__('talents.interview.evaluation_not_allowed'));
            }
            $evaluation = InterviewEvaluation::query()->updateOrCreate(
                ['interview_id' => $interview->id],
                [...$data, 'evaluator_admin_id' => $actor->id],
            );
            $this->event($interview, $actor, 'interview_evaluation_saved', 'manual');

            return $evaluation->load('evaluator.user');
        });
    }

    public function accept(int $applicationId, Admin $actor): TalentApplication
    {
        return DB::transaction(function () use ($applicationId, $actor): TalentApplication {
            $application = TalentApplication::query()->lockForUpdate()->findOrFail($applicationId);
            $this->ensureResponsibleAdmin($application, $actor);
            if ($application->status === TalentApplicationStatusEnum::ACCEPTED) {
                return $application;
            }
            $interview = $application->interviews()->with('evaluation')->lockForUpdate()->latest('round_number')->first();
            if (
                $application->status !== TalentApplicationStatusEnum::INTERVIEW_REQUIRED
                || $interview === null
                || $interview->status !== InterviewStatusEnum::COMPLETED
                || $interview->evaluation === null
            ) {
                throw new ConflictHttpException(__('talents.interview.final_decision_not_allowed'));
            }
            $application->update([
                'status' => TalentApplicationStatusEnum::ACCEPTED,
                'review_decided_by_admin_id' => $actor->id,
                'review_decided_at' => now(),
                'reapply_at' => null,
            ]);
            $this->event($interview, $actor, 'application_accepted', 'manual');

            return $application->refresh();
        });
    }

    private function load(Interview $interview): Interview
    {
        return $interview->refresh()->load([
            'application.talent.user',
            'admins.user',
            'slots',
            'selectedSlot',
            'evaluation.evaluator.user',
        ]);
    }

    private function event(Interview $interview, Admin $actor, string $action, string $source): void
    {
        TalentApplicationEvent::query()->create([
            'application_id' => $interview->application_id,
            'admin_id' => $actor->id,
            'action' => $action,
            'data' => [
                'interview_id' => $interview->id,
                'round_number' => $interview->round_number,
                'transition_source' => $source,
            ],
            'created_at' => now(),
        ]);
    }

    private function ensureResponsibleAdmin(TalentApplication $application, Admin $actor): void
    {
        if ((string) $application->assigned_admin_id !== (string) $actor->getKey()) {
            throw new ConflictHttpException(__('talents.interview.responsible_admin_required'));
        }
    }
}
