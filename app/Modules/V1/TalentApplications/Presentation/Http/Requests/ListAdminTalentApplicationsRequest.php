<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminTalentApplicationsRequest extends FormRequest
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
            'status.*' => [Rule::enum(TalentApplicationStatusEnum::class)],
            'reviewer_admin_id' => ['nullable', 'integer', 'exists:admins,id'],
            'submitted_from' => ['nullable', 'date'],
            'submitted_to' => ['nullable', 'date', 'after_or_equal:submitted_from'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('reviewer_admin_id')) {
            $this->merge(['reviewer_admin_id' => $this->integer('reviewer_admin_id')]);
        }

        if ($this->has('per_page')) {
            $this->merge(['per_page' => $this->integer('per_page')]);
        }
    }
}
