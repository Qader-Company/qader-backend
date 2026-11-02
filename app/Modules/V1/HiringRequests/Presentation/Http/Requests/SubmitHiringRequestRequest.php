<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Requests;

use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringContractTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitHiringRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.talent_id' => ['required', 'integer', 'distinct', 'exists:talents,id'],
            'items.*.contract_type' => ['required', Rule::enum(HiringContractTypeEnum::class)],
            'items.*.interview_required' => ['required', 'boolean'],
            'items.*.salary_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'items' => [
                'description' => 'Talents to hire. Contract defaults are resolved by the backend; the client supplies only the salary and request choices.',
                'example' => [[
                    'talent_id' => 12,
                    'contract_type' => HiringContractTypeEnum::MANAGED->value,
                    'interview_required' => true,
                    'salary_amount' => 25000,
                ]],
            ],
            'items.*.talent_id' => ['example' => 12],
            'items.*.contract_type' => ['example' => HiringContractTypeEnum::MANAGED->value],
            'items.*.interview_required' => ['example' => true],
            'items.*.salary_amount' => ['example' => 25000],
        ];
    }
}
