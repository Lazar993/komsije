<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\PushNotifications\FcmService;
use Illuminate\Console\Command;

/**
 * Diagnose the push-notification pipeline end-to-end.
 *
 * Usage:
 *   php artisan push:diagnose                 # config + token summary
 *   php artisan push:diagnose --user=42       # also send a test push to that user
 *   php artisan push:diagnose --email=a@b.c   # ditto, by email
 *
 * Reports:
 *   - Whether FCM HTTP v1 credentials are loadable.
 *   - Whether the public Web SDK config (vapid key, app id, …) is exposed.
 *   - Per-user device-token counts and timestamps.
 *   - Live FCM send result (which tokens FCM rejected, if any).
 */
final class PushDiagnose extends Command
{
    protected $signature = 'push:diagnose {--user= : Send a test push to this user id}
                                          {--email= : Send a test push to this email}';

    protected $description = 'Diagnose push notification configuration and optionally send a test push';

    public function handle(FcmService $fcm): int
    {
        $this->checkConfig();
        $this->checkTokens();

        $user = $this->resolveUser();

        if ($user === null) {
            $this->newLine();
            $this->line('No --user/--email given; skipping live send. Pass one to test FCM end-to-end.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Sending test push to user #{$user->getKey()} ({$user->email}) …");

        $tokens = $user->routeNotificationForFcm();

        if ($tokens === []) {
            $this->error('User has no device tokens. The PWA never registered one on this account.');
            $this->line('  → Open the installed PWA, tap once to trigger the permission prompt, accept,');
            $this->line('    then re-run this command.');

            return self::FAILURE;
        }

        $this->line('Tokens that will be tried: '.count($tokens));

        $invalid = $fcm->send(
            'Komšije test',
            'Ako vidiš ovu poruku, push notifikacije rade.',
            $tokens,
            ['type' => 'diagnostic', 'url' => '/portal'],
        );

        if ($invalid === []) {
            $this->info('FCM accepted all tokens. Check the device — notification should arrive within seconds.');

            return self::SUCCESS;
        }

        $this->warn('FCM rejected '.count($invalid).' token(s) (now pruned).');
        $this->line('  Likely cause: token expired or app uninstalled. Re-open the PWA to register a fresh token.');

        return self::FAILURE;
    }

    private function checkConfig(): void
    {
        $this->info('— FCM server configuration —');

        $projectId = (string) config('services.fcm.project_id');
        $credsJson = (string) config('services.fcm.credentials_json');
        $credsPath = (string) config('services.fcm.credentials_path');

        $this->line('  project_id: '.($projectId !== '' ? $projectId : '<MISSING>'));
        $this->line('  credentials_json env: '.($credsJson !== '' ? 'set ('.strlen($credsJson).' chars)' : '<not set>'));
        $this->line('  credentials_path env: '.($credsPath !== '' ? $credsPath.' ('.(is_readable($credsPath) ? 'readable' : 'NOT readable').')' : '<not set>'));

        if ($projectId === '' || ($credsJson === '' && ! ($credsPath !== '' && is_readable($credsPath)))) {
            $this->error('  → FCM is not configured. Pushes will be silently dropped.');
        }

        $this->newLine();
        $this->info('— FCM web SDK configuration (exposed to the browser) —');

        $web = (array) config('services.fcm.web', []);

        foreach (['api_key', 'auth_domain', 'project_id', 'messaging_sender_id', 'app_id', 'vapid_key'] as $key) {
            $value = (string) ($web[$key] ?? '');
            $this->line('  '.$key.': '.($value !== '' ? 'set' : '<MISSING>'));
        }

        if (empty($web['api_key']) || empty($web['app_id']) || empty($web['vapid_key'])) {
            $this->error('  → Web SDK config incomplete. The browser will fall back to status="no-config" and never request a token.');
        }
    }

    private function checkTokens(): void
    {
        $this->newLine();
        $this->info('— Device tokens registered —');

        $total = DeviceToken::query()->count();
        $byType = DeviceToken::query()
            ->selectRaw('device_type, count(*) as c')
            ->groupBy('device_type')
            ->pluck('c', 'device_type')
            ->all();

        $this->line('  total: '.$total);

        foreach ($byType as $type => $count) {
            $this->line('  '.($type ?? 'unknown').': '.$count);
        }

        $latest = DeviceToken::query()->orderByDesc('last_used_at')->limit(5)->get();

        if ($latest->isEmpty()) {
            $this->warn('  No device tokens at all. Frontend has never successfully called POST /device-tokens.');

            return;
        }

        $this->line('  latest 5:');

        foreach ($latest as $row) {
            $this->line(sprintf(
                '    user=%d  type=%s  last_used=%s',
                $row->user_id,
                $row->device_type ?? '?',
                optional($row->last_used_at)->toDateTimeString() ?? '-',
            ));
        }
    }

    private function resolveUser(): ?User
    {
        $id = $this->option('user');
        $email = $this->option('email');

        if ($id !== null) {
            return User::query()->find((int) $id);
        }

        if ($email !== null) {
            return User::query()->where('email', $email)->first();
        }

        return null;
    }
}
