<?php

namespace App\Modules\V1\TalentApplications\Application\Services;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Users\Domain\Models\User;
use LogicException;

class AdminApplicationActor
{
    public function resolve(User $user): Admin
    {
        return $user->admin()->first()
            ?? throw new LogicException('Authenticated admin profile was not found.');
    }
}
