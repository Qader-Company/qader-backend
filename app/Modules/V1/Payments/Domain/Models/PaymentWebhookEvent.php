<?php

namespace App\Modules\V1\Payments\Domain\Models;

use App\Modules\V1\Payments\Domain\ValueObjects\PaymentWebhookStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'payment_attempt_id',
        'event_id',
        'event_type',
        'payload',
        'processing_status',
        'processed_at',
        'processing_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'processing_status' => PaymentWebhookStatusEnum::class,
            'processed_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class, 'payment_attempt_id');
    }
}
