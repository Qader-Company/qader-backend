<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTalentToBlacklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['blacklist_reason' => ['required', 'string', 'max:5000']];
    }
}
