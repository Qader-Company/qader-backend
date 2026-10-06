<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Application\Registration\PortalRegistrationFactory;
use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\DB;

class RegisterUseCase
{
    public function __construct(
        private OtpService $otpService,
        private PortalRegistrationFactory $registrationFactory,
    ) {}

    public function execute(array $attributes, PortalTypeEnum $userType): array
    {
        $user = DB::transaction(function () use ($attributes, $userType) {
            return $this->registrationFactory->for($userType)->register($attributes);
        });

        $this->otpService->generateAndSend(
            $user->email,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            $userType,
            trim($user->first_name.' '.$user->last_name),
        );

        return ['user' => $user];
    }
}
