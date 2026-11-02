<?php

namespace App\Modules\V1\HiringRequests\Domain\Models;

use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringContractTypeEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringDecisionEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class HiringRequestItem extends Model
{
    protected $fillable = [
        'hiring_request_id', 'talent_id', 'reference', 'job_title_id',
        'contract_type', 'interview_required', 'status',
        'rejection_reason', 'hold_reason', 'cancellation_reason',
        'client_decision', 'client_decision_reason', 'client_decided_at',
        'talent_decision', 'talent_decision_reason', 'talent_decided_at',
        'decision_due_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            if (blank($item->reference)) {
                do {
                    $reference = 'RI-'.now()->year.'-'.Str::upper(Str::random(8));
                } while (self::query()->where('reference', $reference)->exists());

                $item->reference = $reference;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'contract_type' => HiringContractTypeEnum::class,
            'interview_required' => 'boolean',
            'status' => HiringRequestItemStatusEnum::class,
            'client_decision' => HiringDecisionEnum::class,
            'talent_decision' => HiringDecisionEnum::class,
            'client_decided_at' => 'datetime',
            'talent_decided_at' => 'datetime',
            'decision_due_at' => 'datetime',
        ];
    }

    public function hiringRequest(): BelongsTo
    {
        return $this->belongsTo(HiringRequest::class);
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(HiringOffer::class, 'request_item_id')->latest();
    }

    public function latestOffer(): HasOne
    {
        return $this->hasOne(HiringOffer::class, 'request_item_id')->latestOfMany();
    }

    public function events(): HasMany
    {
        return $this->hasMany(HiringRequestEvent::class, 'request_item_id')->latest('created_at');
    }

    public function interview(): HasOne
    {
        return $this->hasOne(HiringInterview::class, 'request_item_id');
    }
}
