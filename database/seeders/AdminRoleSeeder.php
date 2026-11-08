<?php

namespace Database\Seeders;

use App\Modules\V1\AccessControl\Application\Services\AdminRoleSynchronizer;
use App\Modules\V1\AccessControl\Application\Services\PermissionSynchronizer;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    public function run(
        PermissionSynchronizer $permissions,
        AdminRoleSynchronizer $roles,
    ): void {
        // Keep this seeder safe to run on its own after adding new permissions.
        $permissions->sync();
        $roles->sync();

        $roles->syncSeededRole(
            'admin',
            'Admin',
            'مسؤول',
            array_values(array_filter(
                AdminPermissionEnum::values(),
                static fn (string $permission): bool => ! str_starts_with(
                    $permission,
                    'admin.access-control.'
                ) && ! str_starts_with($permission, 'admin.admins.')
            ))
        );

        $roles->syncSeededRole(
            'reviewer',
            'Reviewer',
            'مراجع',
            [
                AdminPermissionEnum::TALENT_APPLICATIONS_VIEW->value,
                AdminPermissionEnum::TALENT_APPLICATIONS_REVIEW->value,
                AdminPermissionEnum::TALENT_APPLICATIONS_DECIDE->value,
                AdminPermissionEnum::INTERVIEWS_VIEW->value,
                AdminPermissionEnum::INTERVIEWS_MANAGE->value,
                AdminPermissionEnum::INTERVIEWS_EVALUATE->value,
                AdminPermissionEnum::INTERVIEWS_DECIDE->value,
                AdminPermissionEnum::TALENTS_VIEW->value,
            ]
        );
    }
}
