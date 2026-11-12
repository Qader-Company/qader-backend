<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TalentApplicationReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_talent_without_an_application_receives_not_found_instead_of_forbidden(): void
    {
        $this->withoutMiddleware(CheckApiKey::class);
        $user = User::query()->create([
            'first_name' => 'New',
            'last_name' => 'Talent',
            'email' => 'new-talent@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        Talent::query()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['access', 'talent']);

        $this->getJson('/api/v1/talent/application')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('talents.application.not_submitted'));
    }
}
