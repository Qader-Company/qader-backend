<?php

namespace App\Modules\V1\Admins\Infrastructure\Storage;

use App\Modules\V1\Admins\Application\Contracts\AdminAvatarStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PublicAdminAvatarStorage implements AdminAvatarStorageInterface
{
    private const DIRECTORY = 'admins/avatars';

    public function store(UploadedFile $avatar): string
    {
        $path = $avatar->store(self::DIRECTORY, 'public');

        if ($path === false) {
            throw new RuntimeException(
                __('admins.avatar_upload_failed')
            );
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if (
            $path === null
            || Str::startsWith($path, ['http://', 'https://'])
            || ! Str::startsWith($path, self::DIRECTORY.'/')
        ) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
