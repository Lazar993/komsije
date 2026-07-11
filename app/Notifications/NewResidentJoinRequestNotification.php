<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\BuildingJoinRequest;
use App\Notifications\Channels\FcmChannel;
use App\Support\NotificationLaunchUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class NewResidentJoinRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly BuildingJoinRequest $joinRequest)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        unset($notifiable);

        return NotificationLaunchUrl::wrap([
            'type' => 'building_join_request_created',
            'building_id' => $this->joinRequest->building_id,
            'join_request_id' => $this->joinRequest->getKey(),
            'apartment_number' => $this->joinRequest->apartment_number,
            'message' => 'Nova prijava stanara',
            'url' => route('filament.admin.resources.building-join-requests.view', ['record' => $this->joinRequest], false),
        ]);
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        unset($notifiable);

        return [
            'title' => 'Nova prijava stanara',
            'body' => 'Stan ' . $this->joinRequest->apartment_number . ' • Dodirnite za pregled.',
            'data' => NotificationLaunchUrl::wrap([
                'type' => 'building_join_request_created',
                'join_request_id' => $this->joinRequest->getKey(),
                'building_id' => $this->joinRequest->building_id,
                'url' => route('filament.admin.resources.building-join-requests.view', ['record' => $this->joinRequest], false),
            ]),
        ];
    }
}
