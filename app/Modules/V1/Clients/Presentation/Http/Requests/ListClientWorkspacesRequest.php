<?php

namespace App\Modules\V1\Clients\Presentation\Http\Requests;

use App\Modules\V1\Clients\Domain\ValueObjects\ClientListSortEnum;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListClientWorkspacesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(WorkspaceStatusEnum::class)],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'professional_field_id' => ['nullable', 'integer', 'exists:professional_fields,id'],
            'sort' => ['nullable', Rule::enum(ClientListSortEnum::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
