<?php

namespace App\Modules\V1\Admins\Domain\Models;

use App\Modules\V1\Users\Domain\Models\User;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id'])]
class Admin extends Model
{
    use Filterable;

    protected $table = 'admins';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
