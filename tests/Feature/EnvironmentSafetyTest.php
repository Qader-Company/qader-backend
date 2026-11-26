<?php

namespace Tests\Feature;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvironmentSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_testing_environment_uses_the_documented_fixed_otp_with_scope_and_single_use(): void
    {
        $service = app(OtpService::class);
        $email = 'otp-safety@example.test';

        $code = $service->generate(
            $email,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::TALENT,
        );

        $this->assertSame('123456', $code);
        $this->assertFalse($service->validate(
            $email,
            $code,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::CLIENT,
        ));
        $this->assertTrue($service->validate(
            $email,
            $code,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::TALENT,
        ));
        $this->assertFalse($service->validate(
            $email,
            $code,
            OtpPurposeEnum::EMAIL_VERIFICATION,
            PortalTypeEnum::TALENT,
        ));
    }

    public function test_maintenance_route_is_still_protected_by_the_api_key_in_testing(): void
    {
        $this->postJson('/api/maintenance/optimize-clear')
            ->assertUnauthorized();
    }
}
