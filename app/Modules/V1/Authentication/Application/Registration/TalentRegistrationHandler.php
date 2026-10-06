<?php

namespace App\Modules\V1\Authentication\Application\Registration;

use App\Modules\V1\Talents\Application\UseCases\CreateTalentAccountUseCase;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class TalentRegistrationHandler implements PortalRegistrationHandler
{
    public function __construct(private readonly CreateTalentAccountUseCase $createTalentAccount) {}

    public function portal(): PortalTypeEnum
    {
        return PortalTypeEnum::TALENT;
    }

    public function register(array $attributes): User
    {
        return $this->createTalentAccount->execute($attributes);
    }

    public function ensureProfile(User $user): User
    {
        return $this->createTalentAccount->ensureProfile($user);
    }
}
