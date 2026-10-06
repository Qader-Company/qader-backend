<?php

namespace App\Modules\V1\Users\Application\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserAvatarUrlResolver
{
    public static function resolve(?string $avatar): ?string
    {
        if ($avatar === null || $avatar === '') {
            return null;
        }

        if (Str::startsWith($avatar, ['http://', 'https://'])) {
            return $avatar;
        }

        return Storage::disk('public')->url($avatar);
    }
}
