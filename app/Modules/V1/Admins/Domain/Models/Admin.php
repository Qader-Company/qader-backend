<?php

namespace App\Modules\V1\Admins\Domain\Models;

use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\Users\Domain\Models\User;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'phone_country_code', 'phone_number', 'display_name_ar',
])]
class Admin extends Model
{
    use Filterable;

    protected $table = 'admins';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedHiringRequests(): HasMany
    {
        return $this->hasMany(HiringRequest::class, 'assigned_admin_id');
    }
}
