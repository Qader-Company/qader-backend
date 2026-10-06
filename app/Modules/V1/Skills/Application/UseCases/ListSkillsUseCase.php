<?php

namespace App\Modules\V1\Skills\Application\UseCases;

use App\Modules\V1\Skills\Domain\Repositories\SkillRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListSkillsUseCase
{
    public function __construct(
        private readonly SkillRepositoryInterface $skills,
    ) {}

    public function execute(
        bool $forAdmin,
        array $filters = [],
    ): LengthAwarePaginator|Collection {
        if ($forAdmin) {
            return $this->skills->paginate($filters);
        }

        return $this->skills->listActive($filters);
    }
}
