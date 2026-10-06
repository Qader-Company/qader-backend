<?php

namespace App\Modules\V1\Cities\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class CityTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
