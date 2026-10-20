<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\ReapplyPolicyEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectTalentApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reapply_policy' => ['required', Rule::enum(ReapplyPolicyEnum::class)],
            'reapply_after_months' => [
                Rule::requiredIf($this->input('reapply_policy') === ReapplyPolicyEnum::ALLOWED_AFTER_PERIOD->value),
                Rule::prohibitedIf($this->input('reapply_policy') === ReapplyPolicyEnum::BLACKLISTED->value),
                'integer', 'min:1', 'max:120',
            ],
            'blacklist_reason' => [
                Rule::requiredIf($this->input('reapply_policy') === ReapplyPolicyEnum::BLACKLISTED->value),
                Rule::prohibitedIf($this->input('reapply_policy') === ReapplyPolicyEnum::ALLOWED_AFTER_PERIOD->value),
                'string', 'max:5000',
            ],
        ];
    }
}
