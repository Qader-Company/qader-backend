<?php

namespace App\Modules\V1\Clients\Presentation\Http\Requests;

use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceMemberStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListWorkspaceTeamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(WorkspaceMemberStatusEnum::class)],
            'role_id' => ['nullable', 'integer', 'exists:workspace_roles,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
