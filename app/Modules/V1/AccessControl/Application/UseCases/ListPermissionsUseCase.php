<?php

namespace App\Modules\V1\AccessControl\Application\UseCases;

use App\Modules\V1\AccessControl\Domain\Repositories\PermissionRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Collection;

class ListPermissionsUseCase
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissions
    ) {}

    public function execute(PortalTypeEnum $portal): Collection
    {
        return $this->permissions->grouped($portal);
    }
}
