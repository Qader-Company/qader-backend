<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationScreenStateEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTalentReviewFaqRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'screen_states' => ['required', 'array', 'min:1'],
            'screen_states.*' => ['required', 'string', 'distinct', Rule::enum(TalentApplicationScreenStateEnum::class)],
            'question' => ['required', 'array:en,ar'],
            'question.en' => ['required', 'string', 'max:255'],
            'question.ar' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'array:en,ar'],
            'answer.en' => ['required', 'string', 'max:5000'],
            'answer.ar' => ['required', 'string', 'max:5000'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
