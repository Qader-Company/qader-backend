<?php

namespace App\Modules\V1\JobForms\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\JobForms\Application\Services\JobFormWorkflow;
use App\Modules\V1\JobForms\Domain\Models\JobFormSubmission;
use App\Modules\V1\JobForms\Domain\Models\JobPosting;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobSubmissionStatusEnum;
use App\Modules\V1\JobForms\Presentation\Http\Requests\ListJobSubmissionsRequest;
use App\Modules\V1\JobForms\Presentation\Http\Resources\JobSubmissionResource;
use App\Modules\V1\TalentApplications\Application\Services\AdminApplicationActor;
use App\Modules\V1\Talents\Application\UseCases\CreateTalentAccountUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AdminJobSubmissionController extends Controller
{
    private function query(JobPosting $jobForm, array $filters = [])
    {
        return JobFormSubmission::query()->whereHas('version', fn ($q) => $q->where('job_posting_id', $jobForm->id))->with(['version.posting', 'media'])->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('applicant_name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")->orWhere('phone_number', 'like', "%{$v}%")))->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['submitted_from'] ?? null, fn ($q, $v) => $q->whereDate('submitted_at', '>=', $v))->when($filters['submitted_to'] ?? null, fn ($q, $v) => $q->whereDate('submitted_at', '<=', $v))->latest('submitted_at');
    }

    public function index(ListJobSubmissionsRequest $request, JobPosting $jobForm): JsonResponse
    {
        $p = $this->query($jobForm, $request->validated())->paginate($request->integer('per_page', 20));

        return ApiResponse::success(['submissions' => JobSubmissionResource::collection($p->getCollection()), 'meta' => ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage(), 'total' => $p->total()]]);
    }

    public function show(JobPosting $jobForm, JobFormSubmission $submission): JsonResponse
    {
        $this->belongs($jobForm, $submission);

        return ApiResponse::success(new JobSubmissionResource($submission->load(['version.posting', 'answers.field', 'media'])));
    }

    public function downloadCv(JobPosting $jobForm, JobFormSubmission $submission): StreamedResponse
    {
        $this->belongs($jobForm, $submission);
        $media = $submission->getFirstMedia('cv');
        abort_if(! $media, 404, __('job_forms.cv_not_found'));

        return Storage::disk($media->disk)->download($media->getPathRelativeToRoot(), $media->file_name);
    }

    public function destroy(JobPosting $jobForm, JobFormSubmission $submission): JsonResponse
    {
        $this->belongs($jobForm, $submission);
        throw_if($submission->status === JobSubmissionStatusEnum::HIRING_STARTED, ConflictHttpException::class, __('job_forms.cannot_delete_hired'));
        $submission->delete();

        return ApiResponse::deleted();
    }

    public function startHiring(JobPosting $jobForm, JobFormSubmission $submission, AdminApplicationActor $actors, JobFormWorkflow $workflow, CreateTalentAccountUseCase $createTalent): JsonResponse
    {
        $this->belongs($jobForm, $submission);

        return ApiResponse::updated(new JobSubmissionResource($workflow->startHiring($submission, $actors->resolve(request()->user()), $createTalent)), __('job_forms.hiring_started'));
    }

    public function export(ListJobSubmissionsRequest $request, JobPosting $jobForm): StreamedResponse
    {
        $rows = $this->query($jobForm, $request->validated())->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Applicant Name', 'Email', 'Phone', 'Status', 'Submitted At']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->id, $row->applicant_name, $row->email, $row->phone_country_code.$row->phone_number, $row->status->value, $row->submitted_at?->toIso8601String()]);
            } fclose($out);
        }, 'job-form-submissions-'.$jobForm->slug.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function belongs(JobPosting $jobForm, JobFormSubmission $submission): void
    {
        abort_unless($submission->version()->where('job_posting_id', $jobForm->id)->exists(), 404);
    }
}
