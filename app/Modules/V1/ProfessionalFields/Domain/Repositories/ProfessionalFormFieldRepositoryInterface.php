<?php

namespace App\Modules\V1\ProfessionalFields\Domain\Repositories;

interface ProfessionalFormFieldRepositoryInterface
{
    public function configuration(?int $professionalFieldId): array;

    public function listForAdmin(?int $professionalFieldId): array;

    public function sync(
        int $professionalFieldId,
        array $fields,
    ): void;
}
