<?php

namespace Tests\Feature\Profile;

use App\Features\Profile\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->service = new ProfileService;
    }

    private function png(int $width = 120, int $height = 80): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 30, 120, 200));
        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);

        return $data;
    }

    public function test_update_profile_updates_name_username_and_email(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->service->updateProfile($user, [
            'name' => 'Nama Baru',
            'username' => 'namabaru',
            'email' => 'baru@example.com',
        ]));

        $this->assertSame('Nama Baru', $user->refresh()->name);
        $this->assertSame('namabaru', $user->username);
        $this->assertSame('baru@example.com', $user->email);
    }

    public function test_update_password_hashes_new_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('lama')]);

        $this->assertTrue($this->service->updatePassword($user, 'password-baru'));

        $this->assertTrue(Hash::check('password-baru', $user->refresh()->password));
    }

    public function test_update_profile_photo_accepts_base64_data_uri_and_resizes_to_400(): void
    {
        $user = User::factory()->create();

        $path = $this->service->updateProfilePhoto(
            $user,
            'data:image/png;base64,'.base64_encode($this->png(120, 80))
        );

        $this->assertStringStartsWith('profile-photos/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $user->refresh()->profile_photo_path);

        $size = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame(400, $size[0]);
        $this->assertSame(400, $size[1]);
    }

    public function test_update_profile_photo_accepts_a_local_file_path_and_deletes_old_photo(): void
    {
        $user = User::factory()->create(['profile_photo_path' => 'profile-photos/old.png']);
        Storage::disk('public')->put('profile-photos/old.png', 'old');

        $source = storage_path('app/avatar-source.png');
        file_put_contents($source, $this->png(50, 50));

        $path = $this->service->updateProfilePhoto($user, $source);

        Storage::disk('public')->assertMissing('profile-photos/old.png');
        Storage::disk('public')->assertExists($path);
    }

    public function test_update_profile_photo_accepts_raw_bytes(): void
    {
        $user = User::factory()->create();

        $path = $this->service->updateProfilePhoto($user, $this->png(30, 90));

        Storage::disk('public')->assertExists($path);
    }

    public function test_update_profile_photo_rejects_invalid_data(): void
    {
        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Format gambar tidak valid atau tidak didukung.');

        $this->service->updateProfilePhoto($user, 'bukan gambar');
    }

    public function test_delete_profile_photo_removes_file_and_clears_column(): void
    {
        $user = User::factory()->create(['profile_photo_path' => 'profile-photos/foto.png']);
        Storage::disk('public')->put('profile-photos/foto.png', 'data');

        $this->assertTrue($this->service->deleteProfilePhoto($user));

        Storage::disk('public')->assertMissing('profile-photos/foto.png');
        $this->assertNull($user->refresh()->profile_photo_path);
    }

    public function test_delete_profile_photo_without_existing_file_is_safe(): void
    {
        $user = User::factory()->create(['profile_photo_path' => 'profile-photos/missing.png']);

        $this->assertTrue($this->service->deleteProfilePhoto($user));
        $this->assertNull($user->refresh()->profile_photo_path);
    }

    public function test_delete_profile_photo_when_column_is_null(): void
    {
        $user = User::factory()->create(['profile_photo_path' => null]);

        $this->assertTrue($this->service->deleteProfilePhoto($user));
    }
}
