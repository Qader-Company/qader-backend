<?php

namespace App\Modules\V1\ProfessionalFields\Domain\Repositories;

use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProfessionalFieldRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function listActive(array $filters): Collection;

    public function create(array $attributes): ProfessionalField;

    public function findOrFail(int $id): ProfessionalField;

    public function update(
        ProfessionalField $professionalField,
        array $attributes,
    ): ProfessionalField;
}
