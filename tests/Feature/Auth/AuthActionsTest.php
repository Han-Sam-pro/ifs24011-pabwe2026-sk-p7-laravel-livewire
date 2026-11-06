<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Actions\LoginUserAction;
use App\Features\Auth\Actions\RegisterUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_action_accepts_email(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->assertTrue((new LoginUserAction)->execute('user@example.com', 'password'));

        $this->assertTrue(Auth::check());
        $this->assertTrue(Auth::user()->is($user));
    }

    public function test_login_action_accepts_username_and_remember(): void
    {
        User::factory()->create(['username' => 'johan']);

        $this->assertTrue((new LoginUserAction)->execute('johan', 'password', true));

        $this->assertAuthenticated();
    }

    public function test_login_action_returns_false_for_wrong_credentials(): void
    {
        User::factory()->create(['username' => 'johan']);

        $this->assertFalse((new LoginUserAction)->execute('johan', 'salah'));

        $this->assertGuest();
    }

    public function test_register_action_creates_member_with_hashed_password(): void
    {
        $user = (new RegisterUserAction)->execute([
            'name' => 'Baru',
            'username' => 'baru',
            'email' => 'baru@example.com',
            'password' => 'rahasia123',
        ]);

        $this->assertSame('member', $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertDatabaseHas('users', ['username' => 'baru', 'role' => 'member']);
    }

    public function test_register_action_honours_explicit_role(): void
    {
        $user = (new RegisterUserAction)->execute([
            'name' => 'Admin Baru',
            'username' => 'adminbaru',
            'email' => 'adminbaru@example.com',
            'password' => 'rahasia123',
            'role' => 'admin',
        ]);

        $this->assertSame('admin', $user->role);
    }
}
