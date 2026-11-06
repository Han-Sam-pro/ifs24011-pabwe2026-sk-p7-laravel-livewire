<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_helpers(): void
    {
        $admin = User::factory()->admin()->make();
        $member = User::factory()->member()->make();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isMember());
        $this->assertTrue($member->isMember());
        $this->assertFalse($member->isAdmin());
    }

    public function test_profile_photo_url_uses_stored_file_and_falls_back(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'name' => 'Nama Uji',
            'profile_photo_path' => 'profile-photos/foto.png',
        ]);

        Storage::disk('public')->put('profile-photos/foto.png', 'x');

        $this->assertSame(Storage::disk('public')->url('profile-photos/foto.png'), $user->profilePhotoUrl());

        $user->update(['profile_photo_path' => null]);

        $this->assertStringContainsString('ui-avatars.com', $user->profilePhotoUrl());
        $this->assertStringContainsString(urlencode('Nama Uji'), $user->profilePhotoUrl());
    }
}
