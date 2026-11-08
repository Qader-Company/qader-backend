<?php

namespace App\Modules\V1\Authentication\Domain\Services;

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Models\UserDevice;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TokenIssuer
{
    public function create(
        User $user,
        PortalTypeEnum $portal,
        TokenTypeEnum $tokenType,
        ?string $tokenFamilyId = null,
        ?int $userDeviceId = null
    ): array {
        $ttl = config("sanctum.{$tokenType->value}_token_TTL");
        $newAccessToken = $user->createToken(
            name: $portal->value,
            abilities: [
                $tokenType->value,
                $portal->value,
            ],
            expiresAt: Carbon::now()->addMinutes($ttl)
        );

        if ($tokenFamilyId !== null || $userDeviceId !== null) {
            $newAccessToken->accessToken->forceFill([
                'token_family_id' => $tokenFamilyId,
                'user_device_id' => $userDeviceId,
            ])->save();
        }

        return [
            'token' => $newAccessToken->plainTextToken,
            'ttl' => $ttl,
        ];
    }

    public function refreshToken(
        User $user,
        PortalTypeEnum $portal,
        ?UserDevice $device = null
    ): array {
        $currentToken = $user->currentAccessToken();

        return DB::transaction(function () use (
            $user,
            $portal,
            $currentToken,
            $device
        ): array {
            $persistedToken = $currentToken === null
                ? null
                : $user->tokens()
                    ->whereKey($currentToken->getKey())
                    ->lockForUpdate()
                    ->first();

            if ($currentToken !== null && $persistedToken === null) {
                throw new AuthenticationException;
            }

            $tokenFamilyId = $persistedToken?->token_family_id
                ?: (string) Str::uuid();
            $userDeviceId = $device?->getKey()
                ?? $persistedToken?->user_device_id;

            if ($persistedToken?->token_family_id) {
                $user->tokens()
                    ->where('token_family_id', $persistedToken->token_family_id)
                    ->delete();
            } else {
                $persistedToken?->delete();
            }

            return [
                'access_token' => $this->create(
                    $user,
                    $portal,
                    TokenTypeEnum::ACCESS_TOKEN,
                    $tokenFamilyId,
                    $userDeviceId
                ),
                'refresh_token' => $this->create(
                    $user,
                    $portal,
                    TokenTypeEnum::REFRESH_TOKEN,
                    $tokenFamilyId,
                    $userDeviceId
                ),
            ];
        });
    }

    public function revokeCurrentFamily(User $user): void
    {
        $currentToken = $user->currentAccessToken();

        if ($currentToken === null) {
            return;
        }

        $tokenFamilyId = $currentToken->token_family_id;
        $userDeviceId = $currentToken->user_device_id;

        DB::transaction(function () use (
            $user,
            $currentToken,
            $tokenFamilyId,
            $userDeviceId
        ): void {
            if ($tokenFamilyId) {
                $user->tokens()
                    ->where('token_family_id', $tokenFamilyId)
                    ->delete();
            } else {
                $currentToken->delete();
            }

            if ($userDeviceId !== null
                && ! $user->tokens()->where('user_device_id', $userDeviceId)->exists()) {
                $user->userDevices()->whereKey($userDeviceId)->delete();
            }
        });
    }
}
