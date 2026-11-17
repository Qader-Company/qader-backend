<?php

namespace App\Modules\V1\Clients\Presentation\Http\Requests;

use App\Modules\V1\Clients\Domain\ValueObjects\ClientAccountStatusEnum;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientListSortEnum;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientRegistrationPeriodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminClientsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ClientAccountStatusEnum::class)],
            'registration_period' => ['nullable', Rule::enum(ClientRegistrationPeriodEnum::class)],
            'registered_from' => ['nullable', 'date'],
            'registered_to' => ['nullable', 'date', 'after_or_equal:registered_from'],
            'sort' => ['nullable', Rule::enum(ClientListSortEnum::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
