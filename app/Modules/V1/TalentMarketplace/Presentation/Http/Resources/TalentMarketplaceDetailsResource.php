<?php

namespace App\Modules\V1\TalentMarketplace\Presentation\Http\Resources;

use Illuminate\Http\Request;

class TalentMarketplaceDetailsResource extends TalentMarketplaceCardResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'professional_summary' => $this->professional_summary,
            'gender' => $this->enumValue($this->gender),
            'notice_period_days' => $this->notice_period_days,
            'willing_to_relocate' => $this->willing_to_relocate,
            'industry_experience' => $this->whenLoaded('industries', fn () => $this->industries
                ->map(fn ($industry): array => [
                    'id' => $industry->id,
                    'slug' => $industry->slug,
                    'name' => $industry->name,
                ])->values()),
            'skills' => $this->whenLoaded('skills', fn () => $this->skills
                ->map(fn ($skill): array => ['id' => $skill->id, 'name' => $skill->name])
                ->values()),
            'tools' => $this->whenLoaded('tools', fn () => $this->tools
                ->map(fn ($tool): array => ['id' => $tool->id, 'name' => $tool->name])
                ->values()),
            'languages' => $this->whenLoaded('languages', fn () => $this->languages
                ->map(fn ($language): array => [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                    'proficiency_level' => $this->enumOption(
                        'language_proficiencies',
                        $language->pivot->proficiency_level,
                    ),
                ])->values()),
            'work_experience' => $this->whenLoaded('experiences', fn () => $this->experiences
                ->map(fn ($experience): array => [
                    'id' => $experience->id,
                    'job_title' => $experience->job_title,
                    'company_name' => $experience->company_name,
                    'company_url' => $experience->company_url,
                    'started_at' => $experience->started_at?->toDateString(),
                    'ended_at' => $experience->ended_at?->toDateString(),
                    'currently_working' => $experience->currently_working,
                    'responsibilities' => $experience->responsibilities,
                ])->values()),
            'projects' => $this->whenLoaded('projects', fn () => $this->projects
                ->map(fn ($project): array => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'url' => $project->url,
                    'images' => $project->getMedia('images')->map(fn ($media): array => [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                    ])->values(),
                ])->values()),
            'expert_opinion' => null,
        ];
    }
}
