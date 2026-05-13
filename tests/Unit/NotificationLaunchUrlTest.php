<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\NotificationLaunchUrl;
use Tests\TestCase;

class NotificationLaunchUrlTest extends TestCase
{
    public function test_wrap_rewrites_internal_push_urls_to_the_launcher_route(): void
    {
        $wrapped = NotificationLaunchUrl::wrap([
            'type' => 'announcement_created',
            'url' => '/portal/announcements/42?tab=details',
        ]);

        $this->assertSame('/portal/announcements/42?tab=details', $wrapped['target_url']);
        $this->assertSame(
            route('notification.launch', ['target' => '/portal/announcements/42?tab=details'], false),
            $wrapped['url']
        );
    }

    public function test_wrap_leaves_non_internal_urls_unchanged(): void
    {
        $payload = [
            'type' => 'announcement_created',
            'url' => 'https://example.com/outside',
        ];

        $this->assertSame($payload, NotificationLaunchUrl::wrap($payload));
    }
}