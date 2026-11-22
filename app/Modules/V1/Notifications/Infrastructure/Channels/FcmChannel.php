<?php

namespace App\Modules\V1\Notifications\Infrastructure\Channels;

use App\Modules\V1\Notifications\Application\Notifications\PlatformNotification;
use App\Modules\V1\Users\Infrastructure\Notifications\FirebaseCloudMessaging;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Sends a push to every registered device of the user; one failing device never fails the notification. */
class FcmChannel
{
    public function __construct(private readonly FirebaseCloudMessaging $messaging) {}

    public function send(object $notifiable, PlatformNotification $notification): void
    {
        if (blank(config('firebase.credentials')) || ! method_exists($notifiable, 'userDevices')) {
            return;
        }

        $devices = $notifiable->userDevices()->get();
        if ($devices->isEmpty()) {
            return;
        }

        $message = $notification->toFcm($notifiable);
        foreach ($devices as $device) {
            try {
                $this->messaging->send($device, $message['title'], $message['body'], $message['data']);
            } catch (Throwable $exception) {
                Log::warning('FCM push failed.', [
                    'user_id' => $notifiable->getKey(),
                    'device_id' => $device->getKey(),
                    'type' => $message['data']['type'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
