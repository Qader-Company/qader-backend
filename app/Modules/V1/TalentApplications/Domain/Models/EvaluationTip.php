<?php

namespace App\Modules\V1\TalentApplications\Domain\Models;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\EvaluationTipPriorityEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationTip extends Model
{
    public $timestamps = false;

    protected $fillable = ['evaluation_id', 'title', 'priority', 'description', 'display_order'];

    protected function casts(): array
    {
        return ['priority' => EvaluationTipPriorityEnum::class, 'display_order' => 'integer'];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(TalentEvaluation::class, 'evaluation_id');
    }
}
