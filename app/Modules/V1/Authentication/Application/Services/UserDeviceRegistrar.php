<?php

namespace App\Modules\V1\Authentication\Application\Services;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Models\UserDevice;

class UserDeviceRegistrar
{
    public function register(
        User $user,
        ?string $fcmToken,
        ?string $platform
    ): ?UserDevice {
        if ($fcmToken === null || $fcmToken === '') {
            return null;
        }

        return UserDevice::query()->updateOrCreate(
            ['fcm_token_hash' => hash('sha256', $fcmToken)],
            [
                'user_id' => $user->getKey(),
                'platform' => $platform,
                'fcm_token' => $fcmToken,
            ]
        );
    }
}
