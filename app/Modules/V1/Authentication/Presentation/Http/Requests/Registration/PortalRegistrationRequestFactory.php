<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Requests\Registration;

use App\Modules\V1\Clients\Presentation\Http\Requests\RegisterClientRequest;
use App\Modules\V1\Talents\Presentation\Http\Requests\RegisterTalentRequest;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class PortalRegistrationRequestFactory
{
    public function for(PortalTypeEnum $portal): FormRequest
    {
        return match ($portal) {
            PortalTypeEnum::TALENT => app(RegisterTalentRequest::class),
            PortalTypeEnum::CLIENT => app(RegisterClientRequest::class),
            default => throw new InvalidArgumentException("Registration is not available for the {$portal->value} portal."),
        };
    }
}
