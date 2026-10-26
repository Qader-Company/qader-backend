<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveTalentFromBlacklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['reapply_after_months' => ['nullable', 'integer', 'min:1', 'max:120']];
    }
}
