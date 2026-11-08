<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TalentInterviewReasonRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => [
                'description' => 'Reason for requesting a new interview time or apologizing.',
                'example' => 'The proposed time is not suitable. Please schedule another time.',
            ],
        ];
    }
}
