<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Requests;

use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListClientHiringRequestsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(HiringRequestStatusEnum::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
