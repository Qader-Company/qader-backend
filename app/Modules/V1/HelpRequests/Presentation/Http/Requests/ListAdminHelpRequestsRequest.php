<?php

namespace App\Modules\V1\HelpRequests\Presentation\Http\Requests;

use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestListScopeEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestStatusEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminHelpRequestsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'scope' => ['nullable', Rule::enum(HelpRequestListScopeEnum::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::enum(HelpRequestStatusEnum::class)],
            'help_type' => ['nullable', 'array'],
            'help_type.*' => [Rule::enum(HelpRequestTypeEnum::class)],
            'assigned_admin_id' => ['nullable', 'integer', 'exists:admins,id'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
