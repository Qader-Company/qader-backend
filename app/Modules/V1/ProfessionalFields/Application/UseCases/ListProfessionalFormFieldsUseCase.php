<?php

namespace App\Modules\V1\ProfessionalFields\Application\UseCases;

use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFormFieldRepositoryInterface;

class ListProfessionalFormFieldsUseCase
{
    public function __construct(
        private readonly ProfessionalFormFieldRepositoryInterface $formFields,
    ) {}

    public function forAdmin(?int $professionalFieldId): array
    {
        return $this->formFields->listForAdmin($professionalFieldId);
    }

    public function forApplication(int $professionalFieldId): array
    {
        return collect($this->formFields->configuration($professionalFieldId))
            ->flatMap(fn (array $step): array => $step['visible_fields'])
            ->values()
            ->all();
    }
}
