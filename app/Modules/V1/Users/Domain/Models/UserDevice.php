<?php

namespace App\Modules\V1\Users\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id',
        'platform',
        'fcm_token',
        'fcm_token_hash',
    ];

    protected $hidden = [
        'fcm_token',
        'fcm_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'fcm_token' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
