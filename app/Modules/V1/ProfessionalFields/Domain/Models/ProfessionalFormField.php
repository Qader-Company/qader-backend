<?php

namespace App\Modules\V1\ProfessionalFields\Domain\Models;

use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'key',
    'step',
    'is_default',
    'is_required_by_default',
])]
class ProfessionalFormField extends Model
{
    protected $table = 'professional_form_fields';

    public function professionalFields(): BelongsToMany
    {
        return $this->belongsToMany(
            ProfessionalField::class,
            'professional_field_form_field',
        )->withPivot('is_required')->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'key' => ProfessionalFormFieldEnum::class,
            'is_default' => 'boolean',
            'is_required_by_default' => 'boolean',
        ];
    }
}
