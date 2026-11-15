<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectInterviewSlotsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => [
                'description' => 'Reason for rejecting all proposed interview slots.',
                'example' => 'None of the proposed interview times are suitable for me.',
            ],
        ];
    }
}
