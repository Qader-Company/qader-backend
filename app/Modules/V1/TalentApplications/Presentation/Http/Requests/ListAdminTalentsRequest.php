<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminTalentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'state' => ['nullable', 'array'],
            'state.*' => [Rule::in(['accepted', 'rejected', 'blacklisted'])],
            'rejection_stage' => ['nullable', Rule::in(['before_interview', 'after_interview'])],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
