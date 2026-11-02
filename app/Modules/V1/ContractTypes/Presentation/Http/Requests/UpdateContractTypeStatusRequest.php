<?php

namespace App\Modules\V1\ContractTypes\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractTypeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
