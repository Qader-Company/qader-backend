<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRoleStatusRequest extends FormRequest
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
