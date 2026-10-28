<?php

namespace App\Modules\V1\TalentMarketplace\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListWorkspaceTalentSelectionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
