<?php

namespace App\Modules\V1\Admins\Application\UseCases;

use App\Modules\V1\Admins\Application\Contracts\AdminAvatarStorageInterface;
use App\Modules\V1\Admins\Domain\Repositories\AdminRepositoryInterface;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateAdminUseCase
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins,
        private readonly AdminAvatarStorageInterface $avatars
    ) {}

    public function execute(int $adminId, array $attributes): object
    {
        if ($this->admins->hasOwnerRole($adminId)) {
            throw new DomainException(__('admins.owner_cannot_be_modified'));
        }

        $roleIds = array_key_exists('role_ids', $attributes)
            ? Arr::pull($attributes, 'role_ids')
            : null;
        $avatar = Arr::pull($attributes, 'avatar');

        if (! $avatar instanceof UploadedFile) {
            return $this->updateAdmin(
                $adminId,
                $attributes,
                $roleIds
            );
        }

        $currentAdmin = $this->admins->findOrFail($adminId);
        $avatarPath = $this->avatars->store($avatar);
        $attributes['avatar'] = $avatarPath;

        try {
            $admin = $this->updateAdmin(
                $adminId,
                $attributes,
                $roleIds
            );
        } catch (Throwable $exception) {
            $this->avatars->delete($avatarPath);

            throw $exception;
        }

        $this->avatars->delete($currentAdmin->avatar);

        return $admin;
    }

    private function updateAdmin(
        int $adminId,
        array $attributes,
        ?array $roleIds
    ): object {
        return DB::transaction(
            fn (): object => $this->admins->update(
                $adminId,
                $attributes,
                $roleIds
            )
        );
    }
}
