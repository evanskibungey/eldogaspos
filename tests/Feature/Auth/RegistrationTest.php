<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Self-registration is deliberately closed on this system: staff accounts are
 * created by an administrator under Users, and every account carries a role.
 * RegisteredUserController answers 404 to both verbs.
 *
 * These tests originally came from the Breeze scaffolding and asserted that
 * registration worked, so they had been failing ever since it was switched off.
 * They now pin the intended behaviour instead.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_new_users_cannot_self_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
        $this->assertGuest();
        $this->assertSame(0, User::where('email', 'test@example.com')->count());
    }
}
