<?php

namespace App\Modules\V1\Authentication\Application\Registration;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use InvalidArgumentException;

class PortalRegistrationFactory
{
    public function for(PortalTypeEnum $portal): PortalRegistrationHandler
    {
        return match ($portal) {
            PortalTypeEnum::TALENT => app(TalentRegistrationHandler::class),
            PortalTypeEnum::CLIENT => app(ClientRegistrationHandler::class),
            default => throw new InvalidArgumentException("Registration is not available for the {$portal->value} portal."),
        };
    }
}
