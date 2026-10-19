<?php

namespace Database\Seeders;

use App\Modules\V1\AccessControl\Application\Services\AdminRoleSynchronizer;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminRoleEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Database\Seeder;

class PlatformOwnerSeeder extends Seeder
{
    public function run(AdminRoleSynchronizer $roles): void
    {
        $role = $roles->sync()[AdminRoleEnum::SUPER_ADMIN->value];

        $user = User::query()
            ->where('type', PortalTypeEnum::ADMIN)
            ->whereIn('email', [
                'super.admin@qader.com',
                'owner@qader.com',
            ])
            ->orderByRaw('email = ? desc', ['super.admin@qader.com'])
            ->first() ?? new User;

        $user->forceFill([
            'email' => 'super.admin@qader.com',
            'type' => PortalTypeEnum::ADMIN,
            'first_name' => 'Platform',
            'last_name' => 'Admin',
            'password' => 'password',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        Admin::query()->firstOrCreate([
            'user_id' => $user->getKey(),
        ]);

        $user->syncRoles([$role]);
    }
}
