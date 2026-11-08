<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminInterviewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scope' => ['nullable', Rule::in(['all', 'my'])],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::enum(InterviewStatusEnum::class)],
            'type' => ['nullable', 'array'],
            'type.*' => [Rule::enum(InterviewTypeEnum::class)],
            'interviewer_admin_id' => ['nullable', 'integer', 'exists:admins,id'],
            'scheduled_from' => ['nullable', 'date'],
            'scheduled_to' => ['nullable', 'date', 'after_or_equal:scheduled_from'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
