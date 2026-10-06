<?php

namespace App\Modules\V1\Admins\Application\UseCases;

use App\Modules\V1\Admins\Domain\Repositories\AdminRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminsUseCase
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins
    ) {}

    public function execute(array $filters): LengthAwarePaginator
    {
        return $this->admins->paginate($filters);
    }
}
