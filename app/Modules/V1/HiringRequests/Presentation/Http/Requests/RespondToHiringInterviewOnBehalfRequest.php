<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Requests;

use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewResponseEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondToHiringInterviewOnBehalfRequest extends FormRequest
{
    public function rules(): array
    {
        $requiresReason = in_array($this->input('response'), [
            HiringInterviewResponseEnum::CHANGE_REQUESTED->value,
            HiringInterviewResponseEnum::APOLOGIZED->value,
        ], true);

        return [
            'party' => ['required', Rule::in(['talent', 'client'])],
            'response' => ['required', Rule::in([
                HiringInterviewResponseEnum::ACCEPTED->value,
                HiringInterviewResponseEnum::CHANGE_REQUESTED->value,
                HiringInterviewResponseEnum::APOLOGIZED->value,
            ])],
            'reason' => [Rule::requiredIf($requiresReason), 'nullable', 'string', 'max:2000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'party' => [
                'description' => 'The party whose interview response is being recorded.',
                'example' => 'talent',
            ],
            'response' => [
                'description' => 'The interview schedule response recorded by the responsible admin.',
                'example' => HiringInterviewResponseEnum::ACCEPTED->value,
            ],
            'reason' => [
                'description' => 'Required for change_requested and apologized responses.',
                'example' => 'The talent requested another interview time by phone.',
            ],
        ];
    }
}
