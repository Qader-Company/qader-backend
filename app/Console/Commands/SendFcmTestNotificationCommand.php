<?php

namespace App\Console\Commands;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Infrastructure\Notifications\FirebaseCloudMessaging;
use Illuminate\Console\Command;

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

        $messageName = $messaging->send(
            $device,
            (string) $this->option('title'),
            (string) $this->option('body'),
            ['source' => 'local-test']
        );

        $this->info("FCM message sent: {$messageName}");

        return self::SUCCESS;
    }
}
