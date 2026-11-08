<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignHiringRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return ['admin_id' => ['required', 'integer', 'exists:admins,id']];
    }
}
