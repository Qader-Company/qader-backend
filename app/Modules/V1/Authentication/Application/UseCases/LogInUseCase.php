<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Users\Application\Services\UserActivationChecker;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LogInUseCase
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /** @return array{user: User, requires_email_verification: bool} */
    public function execute(
        array $credentials,
        PortalTypeEnum $portal
    ): array {
        $user = $this->userRepository->findWhere([
            'email' => $credentials['email'],
            'type' => $portal,
        ]);

        $isValidCredentials = $user
            && UserActivationChecker::isActive($user)
            && Hash::check($credentials['password'], $user->password);

        if (! $isValidCredentials) {
            throw new UnauthorizedHttpException(
                '',
                __('auth.credentials_mismatch')
            );
        }

        return $this->buildLoginData($user, $portal);
    }

    /** @return array{user: User, requires_email_verification: bool} */
    private function buildLoginData(
        User $user,
        PortalTypeEnum $portal
    ): array {
        $needsVerification = $user->email_verified_at === null;

        if ($needsVerification) {
            $this->otpService->generateAndSend(
                $user->email,
                OtpPurposeEnum::EMAIL_VERIFICATION,
                $portal,
                trim($user->first_name.' '.$user->last_name)
            );
        } else {
            $user = $this->userRepository->update($user, [
                'last_login_at' => now(),
            ]);
        }

        return [
            'user' => $user,
            'requires_email_verification' => $needsVerification,
        ];
    }
}
