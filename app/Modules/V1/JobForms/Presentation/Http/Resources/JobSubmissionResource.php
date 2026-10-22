<?php

namespace App\Modules\V1\JobForms\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cv = $this->relationLoaded('media') ? $this->getFirstMedia('cv') : null;

        return ['id' => $this->id, 'applicant_name' => $this->applicant_name, 'email' => $this->email, 'phone_country_code' => $this->phone_country_code, 'phone_number' => $this->phone_number, 'status' => $this->status->value, 'submitted_at' => $this->submitted_at, 'job' => $this->whenLoaded('version', fn () => ['id' => $this->version->posting->id, 'title' => $this->version->job_details_snapshot['title'] ?? $this->version->posting->title, 'slug' => $this->version->posting->slug, 'version' => $this->version->version_number]), 'answers' => $this->whenLoaded('answers', fn () => $this->answers->mapWithKeys(fn ($answer) => [$answer->field->key->value => $answer->value['answer'] ?? null])), 'cv' => $cv ? ['file_name' => $cv->file_name, 'mime_type' => $cv->mime_type] : null, 'converted_talent_id' => $this->converted_talent_id, 'converted_at' => $this->converted_at];
    }
}
