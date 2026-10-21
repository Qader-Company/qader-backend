<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationScreenStateEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTalentReviewFaqRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'screen_states' => ['sometimes', 'array', 'min:1'],
            'screen_states.*' => ['required_with:screen_states', 'string', 'distinct', Rule::enum(TalentApplicationScreenStateEnum::class)],
            'question' => ['sometimes', 'array:en,ar'],
            'question.en' => ['required_with:question', 'string', 'max:255'],
            'question.ar' => ['required_with:question', 'string', 'max:255'],
            'answer' => ['sometimes', 'array:en,ar'],
            'answer.en' => ['required_with:answer', 'string', 'max:5000'],
            'answer.ar' => ['required_with:answer', 'string', 'max:5000'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
