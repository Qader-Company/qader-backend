<?php

namespace App\Modules\V1\TalentMarketplace\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceTalentSelectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'talent_id' => ['required', 'integer', 'exists:talents,id'],
        ];
    }
}
