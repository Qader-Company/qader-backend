<?php

namespace App\Modules\V1\Admins\Application\UseCases;

use App\Modules\V1\Admins\Domain\Repositories\AdminRepositoryInterface;

class GetAdminUseCase
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins
    ) {}

    public function execute(int $adminId): object
    {
        return $this->admins->findOrFail($adminId);
    }
}
