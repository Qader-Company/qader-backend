<?php

namespace App\Modules\V1\Admins\Application\UseCases;

use App\Modules\V1\Admins\Domain\Repositories\AdminRepositoryInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateAdminStatusUseCase
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins
    ) {}

    public function execute(
        int $adminId,
        bool $isActive,
        int $authenticatedUserId
    ): object {
        if ($this->admins->hasOwnerRole($adminId)) {
            throw new DomainException(__('admins.owner_cannot_be_deactivated'));
        }

        if (
            ! $isActive
            && $this->admins->belongsToUser($adminId, $authenticatedUserId)
        ) {
            throw new DomainException(__('admins.cannot_deactivate_yourself'));
        }

        return DB::transaction(
            fn (): object => $this->admins->updateStatus(
                $adminId,
                $isActive
            )
        );
    }
}
