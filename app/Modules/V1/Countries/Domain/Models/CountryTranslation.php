<?php

namespace App\Modules\V1\Countries\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class CountryTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
