<?php

namespace App\Modules\V1\JobForms\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\JobForms\Application\Services\JobFormWorkflow;
use App\Modules\V1\JobForms\Domain\Models\JobPosting;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobExperienceRangeEnum;
use App\Modules\V1\JobForms\Presentation\Http\Requests\ListPublicJobsRequest;
use App\Modules\V1\JobForms\Presentation\Http\Requests\StorePublicJobSubmissionRequest;
use App\Modules\V1\JobForms\Presentation\Http\Resources\JobFormResource;
use App\Modules\V1\Languages\Domain\Models\Language;
use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\Talents\Domain\ValueObjects\GenderEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\LanguageProficiencyEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\ReligionEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\SeniorityLevelEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use App\Modules\V1\Tools\Domain\Models\Tool;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Http\JsonResponse;

class PublicJobController extends Controller
{
    public function index(ListPublicJobsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $query = $this->active()->with(['professionalField.translations', 'jobTitle', 'versions.fields'])->when($data['search'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('title', 'like', "%{$v}%")->orWhere('summary', 'like', "%{$v}%")->orWhereHas('professionalField.skills', fn ($q) => $q->where('skills.name', 'like', "%{$v}%"))))->when($data['professional_field_id'] ?? null, fn ($q, $v) => $q->where('professional_field_id', $v))->when($data['skill_id'] ?? null, fn ($q, $v) => $q->whereHas('professionalField.skills', fn ($q) => $q->where('skills.id', $v)))->when($data['work_type'] ?? null, fn ($q, $v) => $q->where('work_type', $v))->when($data['work_mode'] ?? null, fn ($q, $v) => $q->where('work_mode', $v));
        $p = $query->latest('published_at')->paginate($data['per_page'] ?? 12);

        return ApiResponse::success(['jobs' => JobFormResource::collection($p->getCollection()), 'meta' => ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage(), 'total' => $p->total()]]);
    }

    public function show(JobPosting $job): JsonResponse
    {
        $this->ensureActive($job);

        return ApiResponse::success(new JobFormResource($job->load(['professionalField.translations', 'jobTitle', 'sections', 'versions.fields'])));
    }

    public function applicationForm(JobPosting $job): JsonResponse
    {
        $this->ensureActive($job);
        $version = $job->versions()->where('status', 'published')->with('fields')->latest('version_number')->firstOrFail();
        $professionalFieldId = (int) data_get($version->job_details_snapshot, 'professional_field.id');

        return ApiResponse::success(['identity_fields' => $version->identity_fields_snapshot, 'field_keys' => $version->fields->pluck('key')->map(fn ($k) => $k->value)->values(), 'options' => ['skills' => Skill::query()->where('is_active', true)->whereHas('professionalFields', fn ($q) => $q->where('professional_fields.id', $professionalFieldId))->get(['skills.id', 'skills.name']), 'tools' => Tool::query()->where('is_active', true)->whereHas('professionalFields', fn ($q) => $q->where('professional_fields.id', $professionalFieldId))->get(['tools.id', 'tools.name']), 'currencies' => Currency::query()->where('is_active', true)->with('translations')->get()->map(fn ($c) => ['id' => $c->id, 'code' => $c->code, 'symbol' => $c->symbol, 'name' => $c->name]), 'countries' => Country::query()->where('is_active', true)->with('translations')->get()->map(fn ($c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name]), 'cities' => City::query()->where('is_active', true)->with('translations')->get()->map(fn ($c) => ['id' => $c->id, 'country_id' => $c->country_id, 'name' => $c->name]), 'languages' => Language::query()->where('is_active', true)->with('translations')->get()->map(fn ($l) => ['id' => $l->id, 'code' => $l->code, 'name' => $l->name]), 'genders' => EnumOptions::make(GenderEnum::class, 'genders'), 'religions' => EnumOptions::make(ReligionEnum::class, 'religions'), 'seniority_levels' => EnumOptions::make(SeniorityLevelEnum::class, 'seniority_levels'), 'language_proficiencies' => EnumOptions::make(LanguageProficiencyEnum::class, 'language_proficiencies'), 'work_types' => EnumOptions::make(WorkTypeEnum::class, 'work_types'), 'work_modes' => EnumOptions::make(WorkModeEnum::class, 'work_modes'), 'experience_ranges' => EnumOptions::make(JobExperienceRangeEnum::class, 'job_experience_ranges')]]);
    }

    public function submit(StorePublicJobSubmissionRequest $request, JobPosting $job, JobFormWorkflow $workflow): JsonResponse
    {
        $this->ensureActive($job);

        return ApiResponse::created(['submission_id' => $workflow->submit($job, $request->validated())->id], __('job_forms.application_received'));
    }

    private function active()
    {
        return JobPosting::query()->where('status', 'published')->where('is_active', true);
    }

    private function ensureActive(JobPosting $job): void
    {
        abort_unless($job->status->value === 'published' && $job->is_active, 404);
    }
}
