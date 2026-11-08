<?php

namespace Database\Seeders;

use App\Modules\V1\AccessControl\Application\Services\AdminRoleSynchronizer;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminRoleEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Database\Seeder;

class DemoAccountSeeder extends Seeder
{
    public function run(AdminRoleSynchronizer $roles): void
    {
        $superAdminRole = $roles->sync()[AdminRoleEnum::SUPER_ADMIN->value];

        foreach (PortalTypeEnum::cases() as $portal) {
            $account = config('demo_accounts.accounts.'.$portal->value);

            $user = User::query()->updateOrCreate(
                [
                    'email' => $account['email'],
                    'type' => $portal,
                ],
                [
                    'first_name' => $account['first_name'],
                    'last_name' => $account['last_name'],
                    'password' => config('demo_accounts.password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            match ($portal) {
                PortalTypeEnum::TALENT => Talent::query()->firstOrCreate(['user_id' => $user->getKey()]),
                PortalTypeEnum::CLIENT => Client::query()->firstOrCreate(['user_id' => $user->getKey()]),
                PortalTypeEnum::ADMIN => $this->createAdminProfile(
                    $user,
                    $superAdminRole
                ),
            };
        }
    }

    private function createAdminProfile(User $user, Role $role): void
    {
        Admin::query()->firstOrCreate(['user_id' => $user->getKey()]);
        $user->syncRoles([$role]);
    }
}
