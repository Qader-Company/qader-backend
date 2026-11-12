<?php

namespace App\Modules\V1\Skills\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListActiveSkillsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Talent portal: when no professional field is sent, default to the talent's own field so the list only
     * contains values that onboarding will accept ("must belong to the professional field").
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('professional_field_id')) {
            return;
        }

        $fieldId = $this->user()?->loadMissing('talent')->talent?->professional_field_id;
        if ($fieldId !== null) {
            $this->merge(['professional_field_id' => $fieldId]);
        }
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'professional_field_id' => [
                'nullable',
                'integer',
                'exists:professional_fields,id',
            ],
        ];
    }
}
