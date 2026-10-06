<?php

namespace App\Modules\V1\Talents\Domain\Models;

use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentWorkType extends Model
{
    protected $guarded = [];

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    protected function casts(): array
    {
        return ['work_type' => WorkTypeEnum::class];
    }
}
