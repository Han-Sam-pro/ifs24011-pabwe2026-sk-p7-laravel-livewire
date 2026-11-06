<?php

namespace Tests\Feature\Livewire;

use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_validates_required_fields(): void
    {
        Livewire::test(Login::class)
            ->call('authenticate')
            ->assertHasErrors(['login', 'password']);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        User::factory()->member()->create(['email' => 'member@example.com', 'password' => 'password']);

        Livewire::test(Login::class)
            ->set('login', 'member@example.com')
            ->set('password', 'salah')
            ->call('authenticate')
            ->assertHasErrors(['login']);

        $this->assertGuest();
    }

    public function test_member_can_login_with_email(): void
    {
        $member = User::factory()->member()->create(['email' => 'member@example.com', 'password' => 'password']);

        Livewire::test(Login::class)
            ->set('login', 'member@example.com')
            ->set('password', 'password')
            ->set('remember', true)
            ->call('authenticate')
            ->assertRedirect(route('stocks.index'));

        $this->assertAuthenticatedAs($member);
    }

    public function test_member_can_login_with_username(): void
    {
        $member = User::factory()->member()->create(['username' => 'memberku', 'password' => 'password']);

        Livewire::test(Login::class)
            ->set('login', 'memberku')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('stocks.index'));

        $this->assertAuthenticatedAs($member);
    }

    public function test_admin_is_redirected_to_admin_area(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com', 'password' => 'password']);

        Livewire::test(Login::class)
            ->set('login', 'admin@example.com')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('admin.users'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_registration_validates_input(): void
    {
        Livewire::test(Register::class)
            ->call('register')
            ->assertHasErrors(['name', 'username', 'email', 'password']);
    }

    public function test_registration_creates_member_and_logs_in(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Pengguna Baru')
            ->set('username', 'penggunabaru')
            ->set('email', 'baru@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('stocks.index'));

        $user = User::where('email', 'baru@example.com')->firstOrFail();
        $this->assertSame('member', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_rejects_duplicate_username_and_email(): void
    {
        User::factory()->create(['username' => 'dipakai', 'email' => 'dipakai@example.com']);

        Livewire::test(Register::class)
            ->set('name', 'Pengguna')
            ->set('username', 'dipakai')
            ->set('email', 'dipakai@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['username', 'email']);
    }

    public function test_registration_rejects_password_mismatch(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Pengguna')
            ->set('username', 'pengguna')
            ->set('email', 'pengguna@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'beda')
            ->call('register')
            ->assertHasErrors(['password']);
    }
}
