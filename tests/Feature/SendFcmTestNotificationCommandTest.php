<?php

namespace Tests\Feature;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Models\UserDevice;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Users\Infrastructure\Notifications\FirebaseCloudMessaging;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SendFcmTestNotificationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_fcm_test_notification_is_stored_and_sent_to_the_latest_device(): void
    {
        $user = User::query()->create([
            'first_name' => 'FCM',
            'last_name' => 'Test',
            'email' => 'fcm-test@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $device = UserDevice::query()->create([
            'user_id' => $user->id,
            'platform' => 'android',
            'fcm_token' => 'valid-fcm-token',
            'fcm_token_hash' => hash('sha256', 'valid-fcm-token'),
        ]);

        $this->mock(FirebaseCloudMessaging::class, function (MockInterface $mock) use ($device): void {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (
                    UserDevice $sentDevice,
                    string $title,
                    string $body,
                    array $data
                ) use ($device): bool {
                    return $sentDevice->is($device)
                        && $title === 'Test title'
                        && $body === 'Test body'
                        && $data['type'] === 'fcm_test'
                        && $data['source'] === 'maintenance-test'
                        && is_string($data['notification_id'])
                        && $data['notification_id'] !== '';
                })
                ->andReturn('projects/qader/messages/test-message');
        });

        $this->artisan('fcm:test', [
            'user' => $user->id,
            '--title' => 'Test title',
            '--body' => 'Test body',
        ])->assertSuccessful();

        $notification = $user->notifications()->sole();

        $this->assertSame('fcm_test', $notification->type);
        $this->assertSame('fcm_test', $notification->data['type']);
        $this->assertSame('Test title', $notification->data['title']);
        $this->assertSame('Test body', $notification->data['body']);
        $this->assertSame('maintenance-test', $notification->data['source']);
        $this->assertNull($notification->read_at);
    }
}
