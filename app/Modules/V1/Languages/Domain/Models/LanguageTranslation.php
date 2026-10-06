<?php

namespace App\Modules\V1\Languages\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
