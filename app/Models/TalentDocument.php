<?php

namespace App\Models;

use App\Enums\TalentDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentDocument extends Model
{
    protected $guarded = [];

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    protected function casts(): array
    {
        return [
            'type' => TalentDocumentType::class,
            'is_current' => 'boolean',
        ];
    }
}
