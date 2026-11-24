<?php

namespace App\Console\Commands;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Infrastructure\Notifications\FirebaseCloudMessaging;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class SendFcmTestNotificationCommand extends Command
{
    protected $signature = 'fcm:test
        {user : User ID}
        {--title=Qader : Notification title}
        {--body=FCM connection is working. : Notification body}';

    protected $description = 'Send an FCM test notification to the latest registered device for a user.';

    public function handle(FirebaseCloudMessaging $messaging): int
    {
        $user = User::query()->find($this->argument('user'));

        if ($user === null) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $device = $user->userDevices()->latest('updated_at')->first();

        if ($device === null) {
            $this->error('The user has no registered FCM device.');

            return self::FAILURE;
        }

        $title = (string) $this->option('title');
        $body = (string) $this->option('body');
        $notificationId = (string) Str::uuid();

        $user->notifications()->create([
            'id' => $notificationId,
            'type' => 'fcm_test',
            'data' => [
                'type' => 'fcm_test',
                'title' => $title,
                'body' => $body,
                'params' => [],
                'target_id' => null,
                'source' => 'maintenance-test',
            ],
        ]);

        $this->info("Notification stored: {$notificationId}");

        try {
            $messageName = $messaging->send(
                $device,
                $title,
                $body,
                [
                    'notification_id' => $notificationId,
                    'type' => 'fcm_test',
                    'target_id' => '',
                    'workspace_id' => '',
                    'source' => 'maintenance-test',
                ]
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("FCM message sent: {$messageName}");

        return self::SUCCESS;
    }
}
