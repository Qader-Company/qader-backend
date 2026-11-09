<?php

namespace App\Modules\V1\HelpRequests\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReassignHelpRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return ['admin_id' => ['required', 'integer', 'exists:admins,id']];
    }
}
