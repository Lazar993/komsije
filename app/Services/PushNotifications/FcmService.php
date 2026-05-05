<?php

declare(strict_types=1);

namespace App\Services\PushNotifications;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin wrapper around Firebase Cloud Messaging HTTP v1.
 *
 * Authenticates with a Google service-account JSON key and sends one
 * notification per device token. Returns the list of tokens that FCM
 * reported as invalid (UNREGISTERED / INVALID_ARGUMENT) so callers can
 * prune stale rows from the device_tokens table.
 */
final class FcmService
{
    private const FCM_ENDPOINT = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';
    private const OAUTH_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /**
     * @param  array<int, string>  $tokens
     * @param  array<string, scalar|null>  $data  Plain key/value pairs only (FCM requires string values).
     * @return array<int, string>  Tokens that should be removed from storage.
     */
    public function send(string $title, string $body, array $tokens, array $data = []): array
    {
        $tokens = array_values(array_unique(array_filter($tokens)));

        if ($tokens === []) {
            return [];
        }

        $projectId = (string) config('services.fcm.project_id');

        if ($projectId === '' || ! $this->hasCredentials()) {
            Log::warning('FCM disabled: project_id or credentials missing.');

            return [];
        }

        try {
            $accessToken = $this->getAccessToken();
        } catch (\Throwable $e) {
            Log::error('FCM access token error', ['exception' => $e->getMessage()]);

            return [];
        }

        $endpoint = sprintf(self::FCM_ENDPOINT, $projectId);
        $invalidTokens = [];

        $stringData = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $stringData[$key] = (string) $value;
        }

        // Always include title/body inside the data payload so the service
        // worker can render the notification. We deliberately do NOT send a
        // top-level `notification` block: that would make FCM auto-display the
        // message in addition to our SW's onBackgroundMessage handler, causing
        // duplicate notifications on web/PWA clients.
        $stringData['title'] = $title;
        $stringData['body'] = $body;

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'data' => $stringData,
                    'webpush' => [
                        'headers' => [
                            'Urgency' => 'high',
                            // Cap how long FCM/Google relays a queued message
                            // when the device is offline. 24h is a sane default
                            // for ticket / announcement notifications: longer
                            // than that and the context is usually stale.
                            'TTL' => '86400s',
                        ],
                        'fcm_options' => array_filter([
                            'link' => $stringData['url'] ?? null,
                        ]),
                    ],
                ],
            ];

            try {
                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->asJson()
                    ->timeout(10)
                    ->post($endpoint, $payload);

                if ($response->successful()) {
                    continue;
                }

                $errorStatus = (string) $response->json('error.status');

                if (in_array($errorStatus, ['NOT_FOUND', 'UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                    $invalidTokens[] = $token;

                    continue;
                }

                Log::warning('FCM send failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            } catch (ConnectionException|RequestException $e) {
                Log::warning('FCM transport error', ['exception' => $e->getMessage()]);
            }
        }

        return $invalidTokens;
    }

    private function hasCredentials(): bool
    {
        return $this->loadServiceAccount() !== null;
    }

    /**
     * @return array{client_email: string, private_key: string, token_uri?: string}|null
     */
    private function loadServiceAccount(): ?array
    {
        $json = (string) config('services.fcm.credentials_json');
        $path = (string) config('services.fcm.credentials_path');

        $raw = '';

        if ($json !== '') {
            $raw = $json;
        } elseif ($path !== '' && is_readable($path)) {
            $raw = (string) file_get_contents($path);
        }

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || ! isset($decoded['client_email'], $decoded['private_key'])) {
            return null;
        }

        return $decoded;
    }

    private function getAccessToken(): string
    {
        return Cache::remember('fcm.access_token', now()->addMinutes(50), function (): string {
            $sa = $this->loadServiceAccount();

            if ($sa === null) {
                throw new RuntimeException('FCM service account credentials not configured.');
            }

            $now = time();
            $header = ['alg' => 'RS256', 'typ' => 'JWT'];
            $claims = [
                'iss' => $sa['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::OAUTH_ENDPOINT,
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $segments = [
                $this->base64Url((string) json_encode($header)),
                $this->base64Url((string) json_encode($claims)),
            ];
            $signingInput = implode('.', $segments);

            $signature = '';
            $success = openssl_sign($signingInput, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256);

            if (! $success) {
                throw new RuntimeException('Unable to sign FCM JWT.');
            }

            $jwt = $signingInput.'.'.$this->base64Url($signature);

            $response = Http::asForm()->timeout(10)->post(self::OAUTH_ENDPOINT, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('FCM OAuth token request failed: '.$response->body());
            }

            return (string) $response->json('access_token');
        });
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
