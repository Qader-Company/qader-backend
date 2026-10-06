<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\Users\Domain\Models\User;

class UserActivationChecker
{
    public static function isActive(User $user): bool
    {
        return (bool) $user->is_active;
    }
}
