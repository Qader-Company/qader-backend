<?php

namespace App\Modules\V1\TalentApplications\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentReviewFaqTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['question', 'answer'];

    public function talentReviewFaq(): BelongsTo
    {
        return $this->belongsTo(TalentReviewFaq::class);
    }
}
