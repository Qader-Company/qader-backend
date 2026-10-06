<?php

namespace App\Modules\V1\Talents\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentExperience extends Model
{
    protected $guarded = [];

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'currently_working' => 'boolean',
        ];
    }
}
