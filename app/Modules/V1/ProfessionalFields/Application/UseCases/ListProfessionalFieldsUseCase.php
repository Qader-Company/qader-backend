<?php

namespace App\Modules\V1\ProfessionalFields\Application\UseCases;

use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFieldRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListProfessionalFieldsUseCase
{
    public function __construct(
        private readonly ProfessionalFieldRepositoryInterface $professionalFields,
    ) {}

    public function execute(
        bool $forAdmin,
        array $filters = [],
    ): LengthAwarePaginator|Collection {
        if ($forAdmin) {
            return $this->professionalFields->paginate($filters);
        }

        return $this->professionalFields->listActive($filters);
    }
}
