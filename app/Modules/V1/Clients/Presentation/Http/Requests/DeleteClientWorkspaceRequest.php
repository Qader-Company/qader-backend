<?php

namespace App\Modules\V1\Clients\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteClientWorkspaceRequest extends FormRequest
{
    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:2000']];
    }
}
