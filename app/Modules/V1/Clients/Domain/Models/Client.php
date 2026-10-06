<?php

namespace App\Modules\V1\Clients\Domain\Models;

use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id'])]
class Client extends Model
{
    protected $table = 'clients';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
