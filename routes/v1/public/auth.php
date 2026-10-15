<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\SocialProviderEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Authentication\Presentation\Http\Controllers\TokenAuthController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

$publicPortals = implode('|', [
    PortalTypeEnum::TALENT->value,
    PortalTypeEnum::CLIENT->value,
]);
$loginPortals = implode('|', PortalTypeEnum::values());

Route::post('{type}/register', [TokenAuthController::class, 'register'])
    ->where('type', $publicPortals)
    ->middleware('throttle:auth-register');

Route::post('{type}/login', [TokenAuthController::class, 'login'])
    ->where('type', $loginPortals)
    ->middleware('throttle:auth-login');

Route::post('{type}/social/{provider}/login', [TokenAuthController::class, 'socialLogin'])
    ->where('type', $publicPortals)
    ->where('provider', implode('|', SocialProviderEnum::values()))
    ->middleware('throttle:auth-login');

Route::delete('logout', [TokenAuthController::class, 'logout'])
    ->middleware(['auth:sanctum', 'throttle:auth-logout']);

Route::get('me', [TokenAuthController::class, 'me'])
    ->middleware(['auth:sanctum', 'abilities:'.TokenTypeEnum::ACCESS_TOKEN->value]);

Route::post('{type}/refresh', [TokenAuthController::class, 'refresh'])
    ->where('type', $loginPortals)
    ->middleware(['auth:sanctum', 'abilities:'.TokenTypeEnum::REFRESH_TOKEN->value, 'throttle:auth-refresh']);

Route::post('{type}/{purpose}/send-otp', [TokenAuthController::class, 'sendOtp'])
    ->where('type', $publicPortals)
    ->where('purpose', implode('|', OtpPurposeEnum::values()))
    ->middleware('throttle:auth-otp-send');

Route::patch('{type}/email-verification', [TokenAuthController::class, 'verifyEmail'])
    ->where('type', $publicPortals)
    ->middleware(['auth:sanctum', 'abilities:'.TokenTypeEnum::VERIFY_TOKEN->value, 'throttle:auth-otp-verify']);

Route::post('{type}/reset-password-verification', [TokenAuthController::class, 'verifyPasswordReset'])
    ->where('type', $publicPortals)
    ->middleware('throttle:auth-otp-verify');

Route::post('{type}/reset-password', [TokenAuthController::class, 'resetPassword'])
    ->where('type', $publicPortals)
    ->middleware(['auth:sanctum', 'abilities:'.TokenTypeEnum::RESET_PASSWORD_TOKEN->value, 'throttle:auth-reset-password']);
