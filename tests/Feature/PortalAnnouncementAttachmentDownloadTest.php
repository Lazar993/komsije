<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PortalAnnouncementAttachmentDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_docx_attachment_is_served_as_download_even_without_download_query(): void
    {
        Storage::fake('local');

        [$user, $building] = $this->createManagerAndBuilding();
        $announcement = Announcement::factory()->create([
            'building_id' => $building->getKey(),
            'author_id' => $user->getKey(),
            'published_at' => now(),
        ]);

        $attachment = $this->createAttachment($announcement, 'meeting-notes.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($user)
            ->withSession(['current_building_id' => $building->getKey()])
            ->get(route('portal.announcements.attachments.download', [$announcement, $attachment]));

        $response->assertOk();
        $this->assertStringStartsWith('attachment;', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_docx_download_with_non_ascii_filename_does_not_throw_server_error(): void
    {
        Storage::fake('local');

        [$user, $building] = $this->createManagerAndBuilding();
        $announcement = Announcement::factory()->create([
            'building_id' => $building->getKey(),
            'author_id' => $user->getKey(),
            'published_at' => now(),
        ]);

        $attachment = $this->createAttachment($announcement, 'račun 100%.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($user)
            ->withSession(['current_building_id' => $building->getKey()])
            ->get(route('portal.announcements.attachments.download', [$announcement, $attachment]).'?download=1');

        $response->assertOk();
        $this->assertStringStartsWith('attachment;', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('filename*=', (string) $response->headers->get('Content-Disposition'));
    }

    /**
     * @return array{0: User, 1: Building}
     */
    private function createManagerAndBuilding(): array
    {
        $user = User::factory()->create();
        $building = Building::factory()->create();

        $building->users()->attach($user, ['role' => BuildingRole::PropertyManager->value]);

        return [$user, $building];
    }

    private function createAttachment(Announcement $announcement, string $originalName, string $mimeType): AnnouncementAttachment
    {
        $path = "announcements/{$announcement->getKey()}/{$originalName}";
        $contents = 'Test DOCX content';

        Storage::disk('local')->put($path, $contents);

        return AnnouncementAttachment::query()->create([
            'announcement_id' => $announcement->getKey(),
            'uploaded_by' => $announcement->author_id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => strlen($contents),
        ]);
    }
}