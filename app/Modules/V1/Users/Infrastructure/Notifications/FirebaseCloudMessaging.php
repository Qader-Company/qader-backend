<?php

namespace App\Modules\V1\Users\Infrastructure\Notifications;

use App\Modules\V1\Users\Domain\Models\UserDevice;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseCloudMessaging
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function send(
        UserDevice $device,
        string $title,
        string $body,
        array $data = []
    ): string {
        $credentialsPath = config('firebase.credentials');

        if (! is_string($credentialsPath) || $credentialsPath === '') {
            throw new RuntimeException('FIREBASE_CREDENTIALS is not configured.');
        }

        if (! is_readable($credentialsPath)) {
            throw new RuntimeException('The Firebase credentials file is not readable.');
        }

        $credentialsData = json_decode(
            (string) file_get_contents($credentialsPath),
            true
        );
        $projectId = is_array($credentialsData)
            ? ($credentialsData['project_id'] ?? null)
            : null;

        if (! is_string($projectId) || $projectId === '') {
            throw new RuntimeException('The Firebase credentials file does not contain project_id.');
        }

        $accessToken = $this->accessToken($credentialsPath);

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Firebase access token could not be created.');
        }

        $response = Http::asJson()
            ->withToken($accessToken)
            ->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                [
                    'message' => [
                        'token' => $device->fcm_token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => collect($data)
                            ->map(fn (mixed $value): string => (string) $value)
                            ->all(),
                    ],
                ]
            );

        if ($this->isUnregistered($response)) {
            $device->delete();

            throw new RuntimeException('The FCM registration is no longer valid.');
        }

        $response->throw();

        return (string) $response->json('name');
    }

    /** Google access tokens live for an hour; reuse one instead of signing a new JWT for every push. */
    private function accessToken(string $credentialsPath): ?string
    {
        $cacheKey = 'fcm.access_token.'.md5($credentialsPath);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = (new ServiceAccountCredentials(self::SCOPE, $credentialsPath))->fetchAuthToken();
        $accessToken = $token['access_token'] ?? null;
        if (is_string($accessToken) && $accessToken !== '') {
            $ttl = max(60, (int) ($token['expires_in'] ?? 3600) - 300);
            Cache::put($cacheKey, $accessToken, $ttl);
        }

        return $accessToken;
    }

    private function isUnregistered(Response $response): bool
    {
        return collect($response->json('error.details', []))
            ->contains(
                fn (mixed $detail): bool => is_array($detail)
                    && ($detail['errorCode'] ?? null) === 'UNREGISTERED'
            );
    }
}
