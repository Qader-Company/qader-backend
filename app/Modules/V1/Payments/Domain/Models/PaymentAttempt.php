<?php

namespace App\Modules\V1\Payments\Domain\Models;

use App\Modules\V1\Payments\Domain\ValueObjects\CheckoutModeEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentAttemptStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'payment_id',
        'gateway_request_id',
        'provider',
        'provider_reference',
        'attempt_status',
        'checkout_mode',
        'checkout_url',
        'client_token',
        'provider_data',
        'failure_reason',
        'expires_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_status' => PaymentAttemptStatusEnum::class,
            'checkout_mode' => CheckoutModeEnum::class,
            'provider_data' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected function clientToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null ? null : decrypt($value),
            set: fn (?string $value): ?string => $value === null ? null : encrypt($value),
        );
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(PaymentWebhookEvent::class);
    }
}
