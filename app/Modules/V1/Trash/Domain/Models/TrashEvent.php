<?php

namespace App\Modules\V1\Trash\Domain\Models;

use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrashEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'resource_type', 'resource_id', 'action', 'actor_user_id', 'portal',
        'workspace_id', 'reason', 'metadata', 'created_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
