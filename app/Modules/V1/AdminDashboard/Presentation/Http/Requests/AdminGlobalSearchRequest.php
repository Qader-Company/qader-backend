<?php

namespace App\Modules\V1\AdminDashboard\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminGlobalSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            'q' => [
                'description' => 'Search requests, talents, clients, or companies.',
                'example' => 'Ahmed',
            ],
            'limit' => [
                'description' => 'Maximum results returned per group.',
                'example' => 5,
            ],
        ];
    }
}
