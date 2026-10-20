<?php

namespace Database\Seeders;

use App\Modules\V1\AccessControl\Application\Services\AdminRoleSynchronizer;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    public function run(AdminRoleSynchronizer $roles): void
    {
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
            ]
        );
    }
}
