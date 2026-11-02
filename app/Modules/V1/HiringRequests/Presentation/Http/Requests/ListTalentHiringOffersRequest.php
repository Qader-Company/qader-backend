<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Requests;

use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTalentHiringOffersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::enum(HiringRequestItemStatusEnum::class)],
            'offer_status' => ['nullable', 'array'],
            'offer_status.*' => [Rule::enum(HiringOfferStatusEnum::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            'search' => [
                'description' => 'Search by offer reference, job title, or company name.',
                'example' => 'Backend Developer',
            ],
            'status' => [
                'description' => 'Filter by one or more hiring item statuses.',
                'example' => [HiringRequestItemStatusEnum::AWAITING_TALENT_RESPONSE->value],
            ],
            'offer_status' => [
                'description' => 'Filter by one or more latest offer statuses.',
                'example' => [HiringOfferStatusEnum::PENDING->value],
            ],
            'per_page' => [
                'description' => 'Number of offers per page.',
                'example' => 15,
            ],
        ];
    }
}
