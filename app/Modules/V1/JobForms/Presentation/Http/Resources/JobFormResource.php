<?php

namespace App\Modules\V1\JobForms\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobFormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isPublic = $request->is('api/v1/public/*');
        $versions = $this->versions;
        $version = ($isPublic ? $versions->filter(fn ($item) => $item->status->value === 'published') : $versions)->sortByDesc('version_number')->first();
        $usesVersionSnapshot = $isPublic || ($version?->status->value === 'draft' && $version->job_details_snapshot !== null);
        $snapshot = $usesVersionSnapshot ? ($version?->job_details_snapshot ?? []) : [];

        return ['id' => $this->id, 'title' => $snapshot['title'] ?? $this->title, 'slug' => $this->slug, 'public_url' => url('/jobs/'.$this->slug), 'summary' => $snapshot['summary'] ?? $this->summary, 'about_role' => $snapshot['about_role'] ?? $this->about_role, 'work_type' => $snapshot['work_type'] ?? $this->work_type->value, 'work_mode' => $snapshot['work_mode'] ?? $this->work_mode->value, 'status' => $this->status->value, 'is_active' => $this->is_active, 'published_at' => $this->published_at, 'submissions_count' => $this->whenCounted('submissions'), 'professional_field' => $usesVersionSnapshot ? ($snapshot['professional_field'] ?? null) : $this->whenLoaded('professionalField', fn () => ['id' => $this->professionalField->id, 'name' => $this->professionalField->name]), 'job_title' => $usesVersionSnapshot ? ($snapshot['job_title'] ?? null) : $this->whenLoaded('jobTitle', fn () => ['id' => $this->jobTitle->id, 'name' => $this->jobTitle->name]), 'sections' => $usesVersionSnapshot ? ($snapshot['sections'] ?? []) : $this->whenLoaded('sections', fn () => $this->sections->groupBy(fn ($row) => $row->section->value)->map->pluck('content')), 'form_version' => $version ? ['id' => $version->id, 'number' => $version->version_number, 'status' => $version->status->value, 'field_ids' => $version->relationLoaded('fields') ? $version->fields->pluck('id')->values() : [], 'field_keys' => $version->relationLoaded('fields') ? $version->fields->pluck('key')->map(fn ($key) => $key->value)->values() : []] : null, 'created_at' => $this->created_at, 'updated_at' => $this->updated_at];
    }
}
