<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewModeEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleTalentInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(InterviewTypeEnum::class)],
            'interviewer_admin_ids' => ['required', 'array', 'min:1'],
            'interviewer_admin_ids.*' => ['required', 'integer', 'distinct', 'exists:admins,id'],
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
