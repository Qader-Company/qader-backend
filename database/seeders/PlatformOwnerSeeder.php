<?php

namespace Database\Seeders;

use App\Modules\V1\AccessControl\Application\Services\PermissionRegistry;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Database\Seeder;

class PlatformOwnerSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'owner',
            'guard_name' => PermissionRegistry::GUARD,
            'portal' => PortalTypeEnum::ADMIN->value,
        ]);

        $role->syncPermissions(
            PermissionRegistry::names(PortalTypeEnum::ADMIN)
        );

        $user = User::query()->firstOrCreate(
            [
                'email' => 'owner@qader.com',
                'type' => PortalTypeEnum::ADMIN,
            ],
            [
                'first_name' => 'Platform',
                'last_name' => 'Owner',
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        Admin::query()->firstOrCreate([
            'user_id' => $user->getKey(),
        ]);

        $user->syncRoles([$role]);
    }
}
