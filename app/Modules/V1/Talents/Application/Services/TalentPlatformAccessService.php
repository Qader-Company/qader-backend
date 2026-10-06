<?php

namespace App\Modules\V1\Talents\Application\Services;

use App\Modules\V1\AccessControl\Domain\ValueObjects\TalentPermissionEnum;
use App\Modules\V1\TalentApplications\Domain\Repositories\TalentApplicationRepositoryInterface;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;

class TalentPlatformAccessService
{
    public function __construct(
        private readonly TalentApplicationRepositoryInterface $applications,
    ) {}

    public function sync(Talent $talent, bool $profileCompleted): void
    {
        $user = User::query()->findOrFail($talent->user_id);
        $permission = TalentPermissionEnum::PLATFORM_ACCESS->value;
        $canAccessPlatform = $profileCompleted
            && $this->applications->existsForTalent($talent->id);

        if ($canAccessPlatform) {
            $user->givePermissionTo($permission);

            return;
        }

        if ($user->hasDirectPermission($permission)) {
            $user->revokePermissionTo($permission);
        }
    }
}
