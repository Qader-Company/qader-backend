<?php

namespace App\Models;

use App\Enums\SalaryPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentWorkPreference extends Model
{
    protected $guarded = [];

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    public function currentCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'current_currency_id');
    }

    public function expectedCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'expected_currency_id');
    }

    protected function casts(): array
    {
        return [
            'available_from' => 'date',
            'willing_to_relocate' => 'boolean',
            'current_salary' => 'decimal:2',
            'expected_salary_min' => 'decimal:2',
            'expected_salary_max' => 'decimal:2',
            'salary_period' => SalaryPeriod::class,
        ];
    }
}
