<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientInterviewReasonRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => [
                'description' => 'Optional reason for requesting a new interview time or apologizing.',
                'example' => 'The proposed time is not suitable. Please schedule another time.',
            ],
        ];
    }
}
