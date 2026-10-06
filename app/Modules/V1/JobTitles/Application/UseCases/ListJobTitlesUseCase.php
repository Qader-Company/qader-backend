<?php

namespace App\Modules\V1\JobTitles\Application\UseCases;

use App\Modules\V1\JobTitles\Domain\Repositories\JobTitleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListJobTitlesUseCase
{
    public function __construct(
        private readonly JobTitleRepositoryInterface $jobTitles,
    ) {}

    public function execute(
        bool $forAdmin,
        array $filters = [],
    ): LengthAwarePaginator|Collection {
        if ($forAdmin) {
            return $this->jobTitles->paginate($filters);
        }

        return $this->jobTitles->listActive($filters);
    }
}
