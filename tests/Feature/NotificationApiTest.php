<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
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

    public function test_talent_notification_endpoints_are_available(): void
    {
        $this->assertNotificationEndpoints(PortalTypeEnum::TALENT);
    }

    public function test_client_notification_endpoints_are_available(): void
    {
        $this->assertNotificationEndpoints(PortalTypeEnum::CLIENT);
    }

    public function test_admin_notification_endpoints_are_available(): void
    {
        $this->assertNotificationEndpoints(PortalTypeEnum::ADMIN);
    }

    private function assertNotificationEndpoints(PortalTypeEnum $portal): void
    {
        $user = $this->user($portal);
        $token = $user->createToken($portal->value, ['access', $portal->value]);
        $notificationId = (string) Str::uuid();
        $workspace = $portal === PortalTypeEnum::CLIENT ? $this->workspaceFor($user) : null;

        $user->notifications()->create([
            'id' => $notificationId,
            'type' => 'hiring_offer_received',
            'data' => [
                'title' => 'New hiring offer',
                'screen' => 'hiring_offer_details',
                'item_id' => 9,
                ...($workspace ? ['workspace_id' => $workspace->id] : []),
            ],
        ]);

        $prefix = $workspace
            ? "/api/v1/client/{$workspace->id}/notifications"
            : "/api/v1/{$portal->value}/notifications";

        $this->withToken($token->plainTextToken)
            ->getJson("{$prefix}?unread_only=true")
            ->assertOk()
            ->assertJsonPath('data.notifications.0.id', $notificationId)
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

    private function workspaceFor(User $user): Workspace
    {
        $client = Client::query()->create(['user_id' => $user->id]);
        $country = Country::query()->create([
            'code' => 'EG',
            'is_active' => true,
            'en' => ['name' => 'Egypt'],
            'ar' => ['name' => 'مصر'],
        ]);
        $workspace = Workspace::query()->create([
            'owner_client_id' => $client->id,
            'name' => 'Notification Workspace',
            'description' => 'Workspace used to verify scoped client notifications.',
            'is_company' => false,
            'country_id' => $country->id,
            'size_band' => '1-10',
        ]);

        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $workspace;
    }
}
