<?php

namespace App\Modules\V1\Authentication\Infrastructure\Social\Registrars;

use App\Modules\V1\Authentication\Application\Registration\PortalRegistrationFactory;
use App\Modules\V1\Authentication\Domain\Contracts\SocialPortalRegistrarInterface;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Str;

class PortalSocialRegistrar implements SocialPortalRegistrarInterface
{
    public function __construct(
        private readonly PortalTypeEnum $portal,
        private readonly PortalRegistrationFactory $registrationFactory,
    ) {}

    public function portal(): PortalTypeEnum
    {
        return $this->portal;
    }

    public function register(string $email, ?string $name, array $attributes): User
    {
        [$firstName, $lastName] = $this->splitName($attributes['name'] ?? $name ?? $email);

        return $this->registrationFactory->for($this->portal)->register([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Str::password(32),
            'email_verified_at' => now(),
        ]);
    }

    public function ensureProfile(User $user, array $attributes): User
    {
        return $this->registrationFactory->for($this->portal)->ensureProfile($user);
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?: 'User', $parts[1] ?? ''];
    }
}
