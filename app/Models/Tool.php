<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tool extends Model
{
    protected $guarded = [];

    public function talents(): BelongsToMany
    {
        return $this->belongsToMany(Talent::class, 'talent_tools');
    }
}
