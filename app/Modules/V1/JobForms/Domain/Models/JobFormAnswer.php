<?php

namespace App\Modules\V1\JobForms\Domain\Models;

use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalFormField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobFormAnswer extends Model
{
    protected $fillable = ['submission_id', 'professional_form_field_id', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(JobFormSubmission::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(ProfessionalFormField::class, 'professional_form_field_id');
    }
}
