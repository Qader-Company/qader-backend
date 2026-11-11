<?php

namespace App\Modules\V1\Clients\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
            // Figma: "I understand that this action is permanent and cannot be undone".
            'confirmation' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return ['confirmation.accepted' => __('clients.deletion_confirmation_required')];
    }
}
