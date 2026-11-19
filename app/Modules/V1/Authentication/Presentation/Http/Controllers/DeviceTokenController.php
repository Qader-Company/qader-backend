<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Authentication\Application\Services\UserDeviceRegistrar;
use App\Modules\V1\Authentication\Presentation\Http\Requests\UpdateDeviceTokenRequest;
use App\Modules\V1\Users\Application\Services\UserActivationChecker;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Users\Presentation\Http\Resources\UserDeviceResource;
use BackedEnum;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeviceTokenController extends Controller
{
    #[Response('{"success":true,"message":"Device token updated successfully.","data":{"id":12,"platform":"android","updated_at":"2026-11-19T14:00:00+02:00"}}')]
    public function update(
        UpdateDeviceTokenRequest $request,
        string $type,
        UserDeviceRegistrar $devices,
    ): JsonResponse {
        $portal = PortalTypeEnum::tryFrom($type) ?? abort(404);
        $user = $request->user();
        $userPortal = $user?->type instanceof BackedEnum
            ? $user->type->value
            : $user?->type;

        if (! $user
            || ! UserActivationChecker::isActive($user)
            || $userPortal !== $portal->value
            || ! $user->tokenCan($portal->value)) {
            throw new AccessDeniedHttpException(__('auth.unauthorized'));
        }

        $device = $devices->syncCurrentSession(
            $user,
            $request->validated('fcm_token'),
            $request->validated('platform'),
        );

        return ApiResponse::updated(
            new UserDeviceResource($device),
            __('auth.device_token_updated'),
        );
    }
}
