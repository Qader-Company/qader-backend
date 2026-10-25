<?php

namespace App\Modules\V1\Industries\Application\UseCases;

use App\Modules\V1\Industries\Domain\Repositories\IndustryRepositoryInterface;

class ListIndustriesUseCase
{
    public function __construct(private readonly IndustryRepositoryInterface $industries) {}

    public function execute(bool $forAdmin, array $filters = []): mixed
    {
        return $forAdmin ? $this->industries->paginate($filters) : $this->industries->listActive();
    }
}
