<?php

namespace App\Modules\V1\AdminDashboard\Presentation\Http\Requests;

use App\Modules\V1\AdminDashboard\Domain\ValueObjects\DashboardPeriodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetAdminDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::enum(DashboardPeriodEnum::class)],
        ];
    }

    public function period(): DashboardPeriodEnum
    {
        return DashboardPeriodEnum::tryFrom((string) $this->input('period'))
            ?? DashboardPeriodEnum::MONTH;
    }

    public function queryParameters(): array
    {
        return [
            'period' => [
                'description' => 'Dashboard reporting period.',
                'example' => DashboardPeriodEnum::MONTH->value,
            ],
        ];
    }
}
