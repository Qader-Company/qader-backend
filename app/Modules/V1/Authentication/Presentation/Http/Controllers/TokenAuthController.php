<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Authentication\Application\UseCases\LogInUseCase;
use App\Modules\V1\Authentication\Application\UseCases\RegisterUseCase;
use App\Modules\V1\Authentication\Application\UseCases\SendOtpUseCase;
use App\Modules\V1\Authentication\Application\UseCases\SocialLoginUseCase;
use App\Modules\V1\Authentication\Application\UseCases\VerifyEmailUseCase;
use App\Modules\V1\Authentication\Application\UseCases\VerifyResetPasswordOTPUseCase;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\SocialProviderEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Authentication\Presentation\Http\Requests\EmailValidationRequest;
use App\Modules\V1\Authentication\Presentation\Http\Requests\LoginRequest;
use App\Modules\V1\Authentication\Presentation\Http\Requests\OTPValidationRequest;
use App\Modules\V1\Authentication\Presentation\Http\Requests\RegisterRequest;
use App\Modules\V1\Authentication\Presentation\Http\Requests\ResetPasswordRequest;
use App\Modules\V1\Authentication\Presentation\Http\Requests\SocialLoginRequest;
use App\Modules\V1\Authentication\Presentation\Http\Requests\VerifyResetPasswordOTPRequest;
use App\Modules\V1\Users\Application\Services\UserResourceResolver;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use BackedEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TokenAuthController extends Controller
{
    public function register(
        RegisterRequest $request,
        string $type,
        RegisterUseCase $register,
        TokenIssuer $tokens
    ): JsonResponse {
        $portal = $this->portal($type);
        $result = $register->execute(
            $request->validated(),
            $portal
        );
        $verificationToken = $tokens->create(
            $result['user'],
            $portal,
            TokenTypeEnum::VERIFY_TOKEN
        );

        return ApiResponse::created(
            [
                'user' => UserResourceResolver::resolve($result['user']),
                'verification_token' => $verificationToken,
            ],
            __('auth.verify_account')
        );
    }

    public function login(
        LoginRequest $request,
        string $type,
        LogInUseCase $login,
        TokenIssuer $tokens
    ): JsonResponse {
        $portal = $this->portal($type);
        $result = $login->execute(
            $request->validated(),
            $portal
        );
        $response = [
            'user' => UserResourceResolver::resolve($result['user']),
        ];

        if ($result['requires_email_verification']) {
            $response['verification_token'] = $tokens->create(
                $result['user'],
                $portal,
                TokenTypeEnum::VERIFY_TOKEN
            );

            return ApiResponse::apiFormat(
                ['data' => $response],
                __('auth.verify_account'),
                Response::HTTP_FORBIDDEN
            );
        }

        $tokenPair = $tokens->refreshToken(
            $result['user'],
            $portal
        );

        return ApiResponse::success(
            $response + $tokenPair,
            __('auth.login_success')
        );
    }

    public function socialLogin(
        SocialLoginRequest $request,
        string $type,
        string $provider,
        SocialLoginUseCase $socialLogin,
        TokenIssuer $tokens
    ): JsonResponse {
        $portal = $this->portal($type);
        $socialProvider = SocialProviderEnum::tryFrom($provider);

        abort_unless($socialProvider, 404);

        $result = $socialLogin->execute(
            $socialProvider,
            $portal,
            $request->validated('token'),
            $request->safe()->except('token')
        );
        $tokenPair = $tokens->refreshToken(
            $result['user'],
            $portal
        );

        return ApiResponse::success(
            [
                'user' => UserResourceResolver::resolve($result['user']),
                ...$tokenPair,
            ],
            __('auth.login_success')
        );
    }

    public function verifyEmail(
        OTPValidationRequest $request,
        string $type,
        VerifyEmailUseCase $verifyEmail,
        TokenIssuer $tokens
    ): JsonResponse {
        $portal = $this->portal($type);
        $user = $request->user();

        $this->ensurePortal($user?->type, $portal);

        $result = $verifyEmail->execute(
            $user,
            $request->validated(),
            $portal
        );
        $tokenPair = $tokens->refreshToken(
            $result['user'],
            $portal
        );

        return ApiResponse::success(
            [
                'user' => UserResourceResolver::resolve($result['user']),
                ...$tokenPair,
            ],
            __('auth.verified_success')
        );
    }

    public function refresh(
        Request $request,
        string $type,
        TokenIssuer $tokens
    ): JsonResponse {
        $portal = $this->portal($type);
        $user = $request->user();

        $this->ensurePortal($user?->type, $portal);

        $tokenPair = $tokens->refreshToken($user, $portal);

        return ApiResponse::success(
            $tokenPair,
            __('auth.token_refreshed')
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::message(__('auth.loggedOut'));
    }

    public function sendOtp(
        EmailValidationRequest $request,
        string $type,
        string $purpose,
        SendOtpUseCase $sendOtp
    ): JsonResponse {
        $portal = $this->portal($type);
        $otpPurpose = OtpPurposeEnum::tryFrom($purpose);

        abort_unless($otpPurpose, 404);

        $sendOtp->execute(
            $request->validated('email'),
            $otpPurpose,
            $portal
        );

        return ApiResponse::message(__('auth.code_sent'));
    }

    public function verifyPasswordReset(
        VerifyResetPasswordOTPRequest $request,
        string $type,
        VerifyResetPasswordOTPUseCase $verifyResetPassword,
        TokenIssuer $tokens
    ): JsonResponse {
        $portal = $this->portal($type);
        $user = $verifyResetPassword->execute(
            $request->validated(),
            $portal
        );
        $resetPasswordToken = $tokens->create(
            $user,
            $portal,
            TokenTypeEnum::RESET_PASSWORD_TOKEN
        );

        return ApiResponse::success([
            'reset_password_token' => $resetPasswordToken,
        ]);
    }

    public function resetPassword(
        ResetPasswordRequest $request,
        string $type,
        UserRepositoryInterface $users
    ): JsonResponse {
        $portal = $this->portal($type);
        $user = $request->user();

        $this->ensurePortal($user?->type, $portal);

        DB::transaction(function () use ($request, $user, $users): void {
            $users->update($user, [
                'password' => $request->validated('password'),
            ]);
            $user->tokens()->delete();
        });

        return ApiResponse::message(__('auth.password_reset_success'));
    }

    private function portal(string $type): PortalTypeEnum
    {
        return PortalTypeEnum::tryFrom($type) ?? abort(404);
    }

    private function ensurePortal(
        mixed $userType,
        PortalTypeEnum $portal
    ): void {
        $value = $userType instanceof BackedEnum
            ? $userType->value
            : $userType;

        if ($value !== $portal->value) {
            throw new AccessDeniedHttpException(__('auth.unauthorized'));
        }
    }
}
