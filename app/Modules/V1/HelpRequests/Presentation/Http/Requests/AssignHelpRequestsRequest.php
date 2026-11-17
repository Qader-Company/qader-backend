<?php

namespace App\Modules\V1\HelpRequests\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignHelpRequestsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'help_request_ids' => ['required', 'array', 'min:1', 'max:100'],
            'help_request_ids.*' => ['integer', 'distinct', 'exists:help_requests,id'],
            'admin_id' => ['required', 'integer', 'exists:admins,id'],
        ];
    }
}
