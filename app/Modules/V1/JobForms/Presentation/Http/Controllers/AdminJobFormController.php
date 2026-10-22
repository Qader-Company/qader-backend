<?php

namespace App\Modules\V1\JobForms\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\JobForms\Application\Services\JobFormWorkflow;
use App\Modules\V1\JobForms\Domain\Models\JobPosting;
use App\Modules\V1\JobForms\Presentation\Http\Requests\ListJobFormsRequest;
use App\Modules\V1\JobForms\Presentation\Http\Requests\SaveJobFormRequest;
use App\Modules\V1\JobForms\Presentation\Http\Resources\JobFormResource;
use App\Modules\V1\TalentApplications\Application\Services\AdminApplicationActor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminJobFormController extends Controller
{
    public function index(ListJobFormsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = JobPosting::query()->with(['professionalField.translations', 'jobTitle', 'versions.fields'])->withCount('submissions');
        $query->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q->where('title', 'like', "%{$value}%")->orWhere('summary', 'like', "%{$value}%")))->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', $filters['is_active']))->when($filters['professional_field_id'] ?? null, fn ($q, $value) => $q->where('professional_field_id', $value));
        $paginator = $query->latest()->paginate($filters['per_page'] ?? 20);

        return ApiResponse::success(['job_forms' => JobFormResource::collection($paginator->getCollection()), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }

    public function store(SaveJobFormRequest $request, AdminApplicationActor $actors, JobFormWorkflow $workflow): JsonResponse
    {
        return ApiResponse::created(new JobFormResource($workflow->save($request->validated(), $actors->resolve($request->user()))));
    }

    public function show(JobPosting $jobForm): JsonResponse
    {
        return ApiResponse::success(new JobFormResource($jobForm->load(['professionalField.translations', 'jobTitle', 'sections', 'versions.fields'])));
    }

    public function update(SaveJobFormRequest $request, JobPosting $jobForm, AdminApplicationActor $actors, JobFormWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new JobFormResource($workflow->save($request->validated(), $actors->resolve($request->user()), $jobForm)));
    }

    public function publish(JobPosting $jobForm, JobFormWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new JobFormResource($workflow->publish($jobForm)), __('job_forms.published'));
    }

    public function activation(Request $request, JobPosting $jobForm, JobFormWorkflow $workflow): JsonResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        return ApiResponse::updated(new JobFormResource($workflow->setActivation($jobForm, $data['is_active'])));
    }

    public function destroy(JobPosting $jobForm): JsonResponse
    {
        $jobForm->update(['is_active' => false]);
        $jobForm->delete();

        return ApiResponse::deleted();
    }
}
