<?php

namespace App\Modules\V1\HiringRequests\Domain\Models;

use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\WorkDayEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiringOffer extends Model
{
    protected $fillable = [
        'request_item_id', 'salary_amount', 'currency_id', 'work_type',
        'work_mode', 'work_start_day', 'work_end_day', 'hybrid_work_days',
        'work_hours', 'status', 'sent_at', 'response_due_at',
    ];

    protected function casts(): array
    {
        return [
            'salary_amount' => 'decimal:4',
            'work_type' => WorkTypeEnum::class,
            'work_mode' => WorkModeEnum::class,
            'work_start_day' => WorkDayEnum::class,
            'work_end_day' => WorkDayEnum::class,
            'hybrid_work_days' => 'array',
            'status' => HiringOfferStatusEnum::class,
            'sent_at' => 'datetime',
            'response_due_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(HiringRequestItem::class, 'request_item_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
