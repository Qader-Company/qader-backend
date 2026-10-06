<?php

namespace App\Modules\V1\Authentication\Application\Registration;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

interface PortalRegistrationHandler
{
    public function portal(): PortalTypeEnum;

    public function register(array $attributes): User;

    public function ensureProfile(User $user): User;
}
