<?php

namespace App\Modules\V1\Admins\Application\UseCases;

use App\Modules\V1\Admins\Application\Contracts\AdminAvatarStorageInterface;
use App\Modules\V1\Admins\Domain\Repositories\AdminRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateAdminUseCase
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins,
        private readonly AdminAvatarStorageInterface $avatars
    ) {}

    public function execute(array $attributes): object
    {
        $roleIds = Arr::pull($attributes, 'role_ids');
        $avatar = Arr::pull($attributes, 'avatar');
        $avatarPath = $avatar instanceof UploadedFile
            ? $this->avatars->store($avatar)
            : null;

        if ($avatarPath !== null) {
            $attributes['avatar'] = $avatarPath;
        }

        try {
            return DB::transaction(
                fn (): object => $this->admins->create(
                    $attributes,
                    $roleIds
                )
            );
        } catch (Throwable $exception) {
            $this->avatars->delete($avatarPath);

            throw $exception;
        }
    }
}
