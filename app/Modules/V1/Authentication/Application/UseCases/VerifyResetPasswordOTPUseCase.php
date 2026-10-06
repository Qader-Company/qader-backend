<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VerifyResetPasswordOTPUseCase
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(array $data, PortalTypeEnum $type)
    {
        $user = $this->userRepository->findWhere([
            'email' => $data['email'],
            'type' => $type,
        ]);

        if (! $user) {
            throw new BadRequestHttpException(__('auth.user_not_found'));
        }

        $isOTPValid = $this->otpService->validate(
            $data['email'],
            $data['otp'],
            OtpPurposeEnum::PASSWORD_RESET,
            $type
        );

        if (! $isOTPValid) {
            throw new BadRequestHttpException(__('auth.invalid_otp'));
        }

        return $user;
    }
}
