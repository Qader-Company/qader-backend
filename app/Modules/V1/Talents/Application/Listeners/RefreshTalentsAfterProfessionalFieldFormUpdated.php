<?php

namespace App\Modules\V1\Talents\Application\Listeners;

use App\Modules\V1\ProfessionalFields\Domain\Events\ProfessionalFieldFormConfigurationUpdated;
use App\Modules\V1\Talents\Application\Services\RefreshProfessionalFieldTalents;

class RefreshTalentsAfterProfessionalFieldFormUpdated
{
    public function __construct(
        private readonly RefreshProfessionalFieldTalents $refreshTalents,
    ) {}

    public function handle(
        ProfessionalFieldFormConfigurationUpdated $event,
    ): void {
        $this->refreshTalents->execute($event->professionalFieldId);
    }
}
