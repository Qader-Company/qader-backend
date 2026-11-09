<?php

namespace App\Modules\V1\HelpRequests\Domain\Models;

use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpRequestEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['help_request_id', 'actor_user_id', 'action', 'data', 'created_at'];

    protected function casts(): array
    {
        return ['data' => 'array', 'created_at' => 'datetime'];
    }

    public function helpRequest(): BelongsTo
    {
        return $this->belongsTo(HelpRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
