<?php

namespace App\Modules\V1\ProfessionalFields\Domain\Models;

use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'key',
])]
class ProfessionalFormField extends Model
{
    protected $table = 'professional_form_fields';

    public function professionalFields(): BelongsToMany
    {
        return $this->belongsToMany(
            ProfessionalField::class,
            'professional_field_form_field',
        )->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'key' => ProfessionalFormFieldEnum::class,
        ];
    }
}
