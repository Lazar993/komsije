<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_profile_image_from_portal(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('portal.profile.update'), [
                'name' => 'Tenant Example',
                'email' => 'tenant@example.com',
                'profile_image' => UploadedFile::fake()->image('avatar.png', 300, 300),
            ])
            ->assertRedirect(route('portal.profile.show'));

        $user->refresh();

        $this->assertNotNull($user->profile_image_path);
        $this->assertTrue(Storage::disk('public')->exists((string) $user->profile_image_path));
    }

    public function test_user_can_remove_existing_profile_image_from_portal(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('profile-images/existing-avatar.jpg', 'avatar');

        $user = User::factory()->create([
            'profile_image_path' => 'profile-images/existing-avatar.jpg',
        ]);

        $this->actingAs($user)
            ->put(route('portal.profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'remove_profile_image' => '1',
            ])
            ->assertRedirect(route('portal.profile.show'));

        $user->refresh();

        $this->assertNull($user->profile_image_path);
        $this->assertFalse(Storage::disk('public')->exists('profile-images/existing-avatar.jpg'));
    }
}