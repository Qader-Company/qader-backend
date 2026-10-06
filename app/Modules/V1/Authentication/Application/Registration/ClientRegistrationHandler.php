<?php

namespace App\Modules\V1\Authentication\Application\Registration;

use App\Modules\V1\Clients\Application\UseCases\CreateClientAccountUseCase;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class ClientRegistrationHandler implements PortalRegistrationHandler
{
    public function __construct(private readonly CreateClientAccountUseCase $createClientAccount) {}

    public function portal(): PortalTypeEnum
    {
        return PortalTypeEnum::CLIENT;
    }

    public function register(array $attributes): User
    {
        return $this->createClientAccount->execute($attributes);
    }

    public function ensureProfile(User $user): User
    {
        return $this->createClientAccount->ensureProfile($user);
    }
}
