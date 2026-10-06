<?php

namespace Database\Seeders;

use App\Modules\V1\AccessControl\Application\Services\PermissionSynchronizer;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(PermissionSynchronizer $permissionSynchronizer): void
    {
        $permissionSynchronizer->sync();
    }
}
