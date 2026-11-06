<?php

namespace Tests\Feature\Livewire;

use App\Features\Profile\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->user = User::factory()->create([
            'name' => 'Nama Lama',
            'username' => 'lama',
            'email' => 'lama@example.com',
            'password' => 'password',
        ]);
        $this->actingAs($this->user);
    }

    public function test_mount_fills_profile_fields(): void
    {
        Livewire::test(Profile::class)
            ->assertSet('name', 'Nama Lama')
            ->assertSet('username', 'lama')
            ->assertSet('email', 'lama@example.com');
    }

    public function test_update_profile_information(): void
    {
        Livewire::test(Profile::class)
            ->set('name', 'Nama Baru')
            ->set('username', 'baru')
            ->set('email', 'baru@example.com')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->user->refresh();
        $this->assertSame('Nama Baru', $this->user->name);
        $this->assertSame('baru', $this->user->username);
        $this->assertSame('baru@example.com', $this->user->email);
    }

    public function test_update_profile_information_validates_unique_values(): void
    {
        User::factory()->create(['username' => 'dipakai', 'email' => 'dipakai@example.com']);

        Livewire::test(Profile::class)
            ->set('name', '')
            ->set('username', 'dipakai')
            ->set('email', 'dipakai@example.com')
            ->call('updateProfileInformation')
            ->assertHasErrors(['name', 'username', 'email']);
    }

    public function test_update_password_rejects_wrong_current_password(): void
    {
        Livewire::test(Profile::class)
            ->set('current_password', 'salah')
            ->set('password', 'password-baru')
            ->set('password_confirmation', 'password-baru')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('password', $this->user->refresh()->password));
    }

    public function test_update_password_success(): void
    {
        Livewire::test(Profile::class)
            ->set('current_password', 'password')
            ->set('password', 'password-baru')
            ->set('password_confirmation', 'password-baru')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('current_password', '')
            ->assertSet('password', '');

        $this->assertTrue(Hash::check('password-baru', $this->user->refresh()->password));
    }

    public function test_photo_upload_opens_crop_modal_and_validates(): void
    {
        $component = Livewire::test(Profile::class);
        $instance = $component->instance();
        $instance->photo = PhotoUploadedFile::make($this->pngPath(), 'photo.png');
        $instance->updatedPhoto();

        $this->assertTrue($instance->showCropModal);

        $instance->photo = PhotoUploadedFile::make($this->textPath(), 'photo.txt');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $instance->updatedPhoto();
    }

    public function test_close_crop_modal_resets_state(): void
    {
        Livewire::test(Profile::class)
            ->set('showCropModal', true)
            ->set('croppedPhoto', 'data:image/png;base64,abc')
            ->call('closeCropModal')
            ->assertSet('showCropModal', false)
            ->assertSet('croppedPhoto', '')
            ->assertSet('photo', null);
    }

    public function test_save_cropped_photo_requires_a_photo(): void
    {
        Livewire::test(Profile::class)
            ->call('saveCroppedPhoto')
            ->assertHasErrors(['photo']);
    }

    public function test_save_cropped_photo_from_upload_fallback(): void
    {
        $component = Livewire::test(Profile::class);
        $instance = $component->instance();
        $instance->photo = PhotoUploadedFile::make($this->pngPath(), 'photo.png');
        $instance->saveCroppedPhoto(app(\App\Features\Profile\Services\ProfileService::class));

        $this->assertNotNull($this->user->refresh()->profile_photo_path);
        Storage::disk('public')->assertExists($this->user->profile_photo_path);
        $this->assertFalse($instance->showCropModal);
    }

    public function test_save_cropped_photo_from_base64(): void
    {
        $data = 'data:image/png;base64,'.base64_encode(file_get_contents($this->pngPath()));

        Livewire::test(Profile::class)
            ->set('croppedPhoto', $data)
            ->call('saveCroppedPhoto')
            ->assertHasNoErrors();

        $this->assertNotNull($this->user->refresh()->profile_photo_path);
    }

    public function test_delete_photo_flow(): void
    {
        Storage::disk('public')->put('profile-photos/old.png', 'x');
        $this->user->update(['profile_photo_path' => 'profile-photos/old.png']);

        Livewire::test(Profile::class)
            ->call('confirmDeletePhoto')
            ->assertSet('confirmingPhotoDeletion', true)
            ->call('cancelDeletePhoto')
            ->assertSet('confirmingPhotoDeletion', false)
            ->call('confirmDeletePhoto')
            ->call('deletePhoto')
            ->assertSet('confirmingPhotoDeletion', false);

        $this->assertNull($this->user->refresh()->profile_photo_path);
        Storage::disk('public')->assertMissing('profile-photos/old.png');
    }

    private function pngPath(): string
    {
        $image = imagecreatetruecolor(20, 20);
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 120, 200));
        $path = storage_path('app/profile-'.uniqid().'.png');
        imagepng($image, $path);

        return $path;
    }

    private function textPath(): string
    {
        $path = storage_path('app/profile-'.uniqid().'.txt');
        file_put_contents($path, 'not an image');

        return $path;
    }
}

class PhotoUploadedFile extends UploadedFile
{
    public string $name;

    public static function make(string $path, string $name): self
    {
        $file = new self($path, $name, null, null, true);
        $file->name = $name;

        return $file;
    }
}
