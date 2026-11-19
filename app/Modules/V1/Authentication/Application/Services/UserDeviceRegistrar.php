<?php

namespace App\Modules\V1\Authentication\Application\Services;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Models\UserDevice;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

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

        return DB::transaction(function () use ($user, $fcmToken, $platform): UserDevice {
            $tokenHash = hash('sha256', $fcmToken);
            $device = UserDevice::query()
                ->where('fcm_token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if ($device === null) {
                return UserDevice::query()->create([
                    'user_id' => $user->getKey(),
                    'platform' => $platform,
                    'fcm_token' => $fcmToken,
                    'fcm_token_hash' => $tokenHash,
                ]);
            }

            if ($device->user_id !== $user->getKey()) {
                DB::table('personal_access_tokens')
                    ->where('user_device_id', $device->getKey())
                    ->update(['user_device_id' => null]);
            }

            $device->update([
                'user_id' => $user->getKey(),
                'platform' => $platform,
                'fcm_token' => $fcmToken,
            ]);

            return $device->refresh();
        });
    }

    public function syncCurrentSession(
        User $user,
        string $fcmToken,
        string $platform,
    ): UserDevice {
        return DB::transaction(function () use ($user, $fcmToken, $platform): UserDevice {
            $device = $this->register($user, $fcmToken, $platform);
            $currentToken = $user->currentAccessToken();

            if (! $currentToken instanceof PersonalAccessToken) {
                return $device;
            }

            $previousDeviceId = $currentToken->user_device_id;

            if ($currentToken->token_family_id !== null) {
                $user->tokens()
                    ->where('token_family_id', $currentToken->token_family_id)
                    ->update(['user_device_id' => $device->getKey()]);
            } else {
                $currentToken->forceFill(['user_device_id' => $device->getKey()])->save();
            }

            if ($previousDeviceId !== null
                && $previousDeviceId !== $device->getKey()
                && ! DB::table('personal_access_tokens')->where('user_device_id', $previousDeviceId)->exists()) {
                $user->userDevices()->whereKey($previousDeviceId)->delete();
            }

            return $device;
        });
    }
}
