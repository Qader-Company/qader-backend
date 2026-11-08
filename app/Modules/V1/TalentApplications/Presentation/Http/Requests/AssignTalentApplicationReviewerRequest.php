<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTalentApplicationReviewerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['required', 'integer', 'distinct', 'exists:talent_applications,id'],
            'reviewer_admin_id' => ['required', 'integer', 'exists:admins,id'],
        ];
    }
}
