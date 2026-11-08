<?php

namespace App\Console\Commands;

use App\Modules\V1\AccessControl\Application\Services\AdminRoleSynchronizer;
use App\Modules\V1\AccessControl\Application\Services\PermissionSynchronizer;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminRoleEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Ejarnutowski\LaravelApiKey\Models\ApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BootstrapAccessControlCommand extends Command
{
    protected $signature = 'qader:bootstrap-access
        {email : Email address for the first admin}
        {--first-name=System : First name}
        {--last-name=Admin : Last name}
        {--password= : Password; omit it to be prompted}
        {--api-key-name=local-client : API key name}';

    protected $description = 'Creates or updates the platform super admin, permissions, and API key.';

    public function handle(
        PermissionSynchronizer $permissionSynchronizer,
        AdminRoleSynchronizer $roleSynchronizer
    ): int
    {
        $password = $this->option('password') ?: $this->secret('Admin password (min. 8 characters)');

        if (! is_string($password) || mb_strlen($password) < 8) {
            $this->error('A password with at least 8 characters is required.');

            return self::FAILURE;
        }

        $permissionSynchronizer->sync();
        $roles = $roleSynchronizer->sync();

        [$user, $apiKey] = DB::transaction(function () use ($password, $roles): array {
            $role = $roles[AdminRoleEnum::SUPER_ADMIN->value];

            $user = User::updateOrCreate(
                [
                    'email' => $this->argument('email'),
                    'type' => PortalTypeEnum::ADMIN,
                ],
                [
                    'first_name' => $this->option('first-name'),
                    'last_name' => $this->option('last-name'),
                    'password' => $password,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            Admin::firstOrCreate(['user_id' => $user->id]);
            $user->syncRoles([$role]);

            $apiKey = ApiKey::withoutEvents(fn () => ApiKey::firstOrCreate(
                ['name' => $this->option('api-key-name')],
                ['key' => ApiKey::generate(), 'active' => true]
            ));

            return [$user, $apiKey];
        });

        $this->info("Platform super admin ready: {$user->email}");
        $this->line("X-Authorization: {$apiKey->key}");

        return self::SUCCESS;
    }
}
