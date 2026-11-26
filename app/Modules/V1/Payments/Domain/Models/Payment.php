<?php

namespace App\Modules\V1\Payments\Domain\Models;

use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Payments\Domain\ValueObjects\PaidServiceEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentStatusEnum;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'reference',
        'idempotency_key',
        'request_fingerprint',
        'payer_user_id',
        'owner_client_id',
        'talent_id',
        'talent_application_id',
        'service',
        'amount',
        'currency',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'service' => PaidServiceEnum::class,
            'status' => PaymentStatusEnum::class,
            'amount' => 'decimal:4',
            'paid_at' => 'datetime',
        ];
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    public function ownerClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'owner_client_id');
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    public function talentApplication(): BelongsTo
    {
        return $this->belongsTo(TalentApplication::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }
}
