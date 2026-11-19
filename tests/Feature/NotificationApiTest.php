<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CheckApiKey::class);
    }

    public function test_each_portal_can_update_its_current_device_token(): void
    {
        foreach (PortalTypeEnum::cases() as $portal) {
            Auth::forgetGuards();
            $user = $this->user($portal);
            $token = $user->createToken($portal->value, ['access', $portal->value]);
            $fcmToken = "{$portal->value}-fcm-token";

            $this->withToken($token->plainTextToken)
                ->putJson("/api/v1/auth/{$portal->value}/device-token", [
                    'fcm_token' => $fcmToken,
                    'platform' => 'android',
                ])
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.platform', 'android');

            $device = $user->userDevices()->latest('id')->firstOrFail();
            $this->assertSame($fcmToken, $device->fcm_token);
            $this->assertSame(
                $device->id,
                $user->tokens()->whereKey($token->accessToken->id)->value('user_device_id'),
            );
        }
    }

    public function test_device_token_route_rejects_a_token_from_another_portal(): void
    {
        $user = $this->user(PortalTypeEnum::TALENT);
        $token = $user->createToken('talent', ['access', 'talent']);

        $this->withToken($token->plainTextToken)
            ->putJson('/api/v1/auth/client/device-token', [
                'fcm_token' => 'talent-device-token',
                'platform' => 'android',
            ])
            ->assertForbidden();
    }

    public function test_notification_endpoints_are_available_in_every_portal(): void
    {
        foreach (PortalTypeEnum::cases() as $portal) {
            Auth::forgetGuards();
            $user = $this->user($portal);
            $token = $user->createToken($portal->value, ['access', $portal->value]);
            $notificationId = (string) Str::uuid();

            $user->notifications()->create([
                'id' => $notificationId,
                'type' => 'hiring_offer_received',
                'data' => [
                    'title' => 'New hiring offer',
                    'screen' => 'hiring_offer_details',
                    'item_id' => 9,
                ],
            ]);

            $prefix = "/api/v1/{$portal->value}/notifications";

            $this->withToken($token->plainTextToken)
                ->getJson("{$prefix}?unread_only=true")
                ->assertOk()
                ->assertJsonPath('data.items.0.id', $notificationId)
                ->assertJsonPath('data.meta.total', 1);

            $this->withToken($token->plainTextToken)
                ->getJson("{$prefix}/unread-count")
                ->assertOk()
                ->assertJsonPath('data.unread_count', 1);

            $this->withToken($token->plainTextToken)
                ->patchJson("{$prefix}/{$notificationId}/read")
                ->assertOk()
                ->assertJsonPath('data.id', $notificationId);

            $this->withToken($token->plainTextToken)
                ->patchJson("{$prefix}/read-all")
                ->assertOk()
                ->assertJsonPath('data.unread_count', 0);
        }
    }

    private function user(PortalTypeEnum $portal): User
    {
        return User::query()->create([
            'first_name' => ucfirst($portal->value),
            'last_name' => 'User',
            'email' => "{$portal->value}.notifications@example.test",
            'password' => 'Password123!',
            'type' => $portal,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
