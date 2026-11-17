<?php

namespace App\Modules\V1\Industries\Domain\Models;

use App\Modules\V1\Trash\Domain\Concerns\HasTrashLifecycle;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;

class Industry extends Model implements TranslatableContract
{
    use Filterable, HasTrashLifecycle, Translatable;

    public array $translatedAttributes = ['name'];

    protected $fillable = ['slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
