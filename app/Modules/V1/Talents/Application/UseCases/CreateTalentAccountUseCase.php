<?php

namespace App\Modules\V1\Talents\Application\UseCases;

use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class CreateTalentAccountUseCase
{
    public function execute(array $attributes): User
    {
        $user = User::create([
            'first_name' => $attributes['first_name'],
            'last_name' => $attributes['last_name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
            'email_verified_at' => $attributes['email_verified_at'] ?? null,
            'type' => PortalTypeEnum::TALENT,
        ]);

        return $this->ensureProfile($user);
    }

    public function ensureProfile(User $user): User
    {
        Talent::firstOrCreate(['user_id' => $user->id]);

        return $user->loadMissing('talent');
    }
}
