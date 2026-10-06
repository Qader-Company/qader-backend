<?php

namespace App\Modules\V1\TalentApplications\Domain\Models;

use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentApplication extends Model
{
    protected $fillable = [
        'talent_id',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }
}
