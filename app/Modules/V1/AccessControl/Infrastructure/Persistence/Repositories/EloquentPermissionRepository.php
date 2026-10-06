<?php

namespace App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories;

use App\Modules\V1\AccessControl\Application\Services\PermissionRegistry;
use App\Modules\V1\AccessControl\Domain\Repositories\PermissionRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentPermissionRepository implements PermissionRepositoryInterface
{
    public function grouped(PortalTypeEnum $portal): Collection
    {
        return DB::table('permissions')
            ->where('portal', $portal->value)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'portal',
            ])
            ->groupBy(function (object $permission) use ($portal): string {
                return PermissionRegistry::find(
                    $portal,
                    $permission->name
                )?->section() ?? 'other';
            })
            ->map(function (Collection $permissions, string $section): array {
                return [
                    'section' => $section,
                    'permissions' => $permissions->values(),
                ];
            })
            ->values();
    }
}
