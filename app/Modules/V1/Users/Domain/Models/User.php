<?php

namespace App\Modules\V1\Users\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends \App\Models\User
{
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'type',
        'avatar', 'is_active', 'last_login_at',
        'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'type' => PortalTypeEnum::class,
        ];
    }

    public function talent(): HasOne
    {
        return $this->hasOne(Talent::class);
    }

    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }
}
