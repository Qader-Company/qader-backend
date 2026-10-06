<?php

namespace App\Modules\V1\Currencies\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
