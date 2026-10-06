<?php

namespace App\Modules\V1\ProfessionalFields\Domain\Events;

readonly class ProfessionalFieldFormConfigurationUpdated
{
    public function __construct(
        public int $professionalFieldId,
    ) {}
}
