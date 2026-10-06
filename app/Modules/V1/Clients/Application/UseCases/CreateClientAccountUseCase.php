<?php

namespace App\Modules\V1\Clients\Application\UseCases;

use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class CreateClientAccountUseCase
{
    public function execute(array $attributes): User
    {
        $user = User::create([
            'first_name' => $attributes['first_name'],
            'last_name' => $attributes['last_name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
            'email_verified_at' => $attributes['email_verified_at'] ?? null,
            'type' => PortalTypeEnum::CLIENT,
        ]);

        return $this->ensureProfile($user);
    }

    public function ensureProfile(User $user): User
    {
        Client::firstOrCreate(['user_id' => $user->id]);

        return $user->loadMissing('client');
    }
}
