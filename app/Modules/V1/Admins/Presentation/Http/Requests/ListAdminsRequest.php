<?php

namespace App\Modules\V1\Admins\Presentation\Http\Requests;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'role_id' => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')
                    ->where('portal', PortalTypeEnum::ADMIN->value),
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);
        }

        if ($this->has('role_id')) {
            $this->merge([
                'role_id' => $this->integer('role_id'),
            ]);
        }

        if ($this->has('per_page')) {
            $this->merge([
                'per_page' => $this->integer('per_page'),
            ]);
        }
    }
}
