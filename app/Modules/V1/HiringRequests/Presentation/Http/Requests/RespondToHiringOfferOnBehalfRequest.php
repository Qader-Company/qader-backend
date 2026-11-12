<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondToHiringOfferOnBehalfRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['accepted', 'rejected'])],
            'reason' => [
                Rule::requiredIf($this->input('decision') === 'rejected'),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => __('hiring.rejection_reason_required'),
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'decision' => [
                'description' => 'The offer response recorded by the responsible admin on the talent\'s behalf.',
                'example' => 'accepted',
            ],
            'reason' => [
                'description' => 'Required when the decision is rejected.',
                'example' => 'The talent declined the offer after being contacted by Qader.',
            ],
        ];
    }
}
