<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewModeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplaceTalentInterviewSlotsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'meeting_url' => ['nullable', 'url:http,https', 'max:2048'],
            'slots' => ['required', 'array', 'min:1', 'max:20'],
            'slots.*.mode' => ['required', Rule::enum(InterviewModeEnum::class)],
            'slots.*.start' => ['required', 'date', 'after:now', 'distinct'],
            'slots.*.location' => ['nullable', 'required_if:slots.*.mode,offline', 'string', 'max:500'],
            'slots.*.latitude' => ['nullable', 'required_with:slots.*.longitude', 'numeric', 'between:-90,90'],
            'slots.*.longitude' => ['nullable', 'required_with:slots.*.latitude', 'numeric', 'between:-180,180'],
        ];
    }
}
