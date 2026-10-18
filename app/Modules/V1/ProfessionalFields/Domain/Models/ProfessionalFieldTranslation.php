<?php

namespace App\Modules\V1\ProfessionalFields\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalFieldTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'description'];
}
