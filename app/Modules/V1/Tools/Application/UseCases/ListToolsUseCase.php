<?php

namespace App\Modules\V1\Tools\Application\UseCases;

use App\Modules\V1\Tools\Domain\Repositories\ToolRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListToolsUseCase
{
    public function __construct(
        private readonly ToolRepositoryInterface $tools,
    ) {}

    public function execute(
        bool $forAdmin,
        array $filters = [],
    ): LengthAwarePaginator|Collection {
        if ($forAdmin) {
            return $this->tools->paginate($filters);
        }

        return $this->tools->listActive($filters);
    }
}
