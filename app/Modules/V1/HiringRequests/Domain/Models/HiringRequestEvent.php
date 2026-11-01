<?php

namespace App\Modules\V1\HiringRequests\Domain\Models;

use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiringRequestEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'hiring_request_id', 'request_item_id', 'actor_user_id',
        'action', 'data', 'created_at',
    ];

    protected function casts(): array
    {
        return ['data' => 'array', 'created_at' => 'datetime'];
    }

    public function hiringRequest(): BelongsTo
    {
        return $this->belongsTo(HiringRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(HiringRequestItem::class, 'request_item_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
