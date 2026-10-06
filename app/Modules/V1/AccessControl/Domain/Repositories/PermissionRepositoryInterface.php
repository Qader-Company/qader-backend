<?php

namespace App\Modules\V1\AccessControl\Domain\Repositories;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Collection;

interface PermissionRepositoryInterface
{
    public function grouped(PortalTypeEnum $portal): Collection;
}
