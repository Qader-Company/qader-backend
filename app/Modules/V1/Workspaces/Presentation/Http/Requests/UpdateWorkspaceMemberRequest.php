<?php

namespace App\Modules\V1\Workspaces\Presentation\Http\Requests;

use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceMemberStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => ['sometimes', Rule::enum(WorkspaceMemberStatusEnum::class)], 'role_ids' => ['sometimes', 'array', 'min:1'], 'role_ids.*' => ['integer', 'distinct']];
    }
}
