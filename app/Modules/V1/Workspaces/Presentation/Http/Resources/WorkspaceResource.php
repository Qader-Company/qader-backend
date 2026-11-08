<?php

namespace App\Modules\V1\Workspaces\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo_url' => $this->getFirstMediaUrl('logo') ?: null,
            'industries' => $this->whenLoaded('industries', fn () => $this->industries
                ->map(fn ($industry) => [
                    'id' => $industry->id,
                    'slug' => $industry->slug,
                    'name' => $industry->name,
                ])
                ->values()),
            'professional_fields' => $this->whenLoaded('professionalFields', fn () => $this->professionalFields
                ->map(fn ($field) => [
                    'id' => $field->id,
                    'slug' => $field->slug,
                    'name' => $field->name,
                ])
                ->values()),
            'description' => $this->description,
            'is_company' => $this->is_company,
            'country' => $this->whenLoaded('country', fn () => [
                'id' => $this->country->id,
                'code' => $this->country->code,
                'name' => $this->country->name,
            ]),
            'city' => $this->whenLoaded('city', fn () => $this->city ? [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ] : null),
            'size_band' => $this->size_band->value,
            'website_url' => $this->website_url,
            'social_links' => $this->whenLoaded('socialLinks', fn () => $this->socialLinks
                ->map(fn ($link) => [
                    'id' => $link->id,
                    'platform' => $link->platform->value,
                    'url' => $link->url,
                ])),
            'members_count' => (int) ($this->members_count ?? 0),
            'created_at' => $this->created_at,
        ];
    }
}
