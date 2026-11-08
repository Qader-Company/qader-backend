<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Users\Domain\Models\User;
use LogicException;

class AdminHiringRequestActor
{
    public function resolve(User $user): Admin
    {
        return $user->admin()->with('user')->first()
            ?? throw new LogicException('Authenticated admin profile was not found.');
    }
}
