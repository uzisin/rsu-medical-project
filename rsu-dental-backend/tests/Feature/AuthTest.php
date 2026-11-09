<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // requests from the frontend address are treated as stateful (session cookie)
        $this->withHeaders(['Referer' => 'http://localhost:5173']);
    }

    // a quick way to create an account
    private function makeUser(array $attributes = []): User
    {
        return User::create($attributes + [
            'name' => 'Test User',
            'username' => 'tester',
            'password' => 'secret-pass-1',
            'role' => 'receptionist',
            'status' => 'active',
        ]);
    }

    public function test_user_can_log_in(): void
    {
        $user = $this->makeUser();

        // the username is not case sensitive
        $this->postJson('/api/login', ['username' => 'Tester', 'password' => 'secret-pass-1'])
            ->assertOk()
            ->assertJsonPath('data.username', 'tester')
            ->assertJsonPath('data.role', 'receptionist')
            ->assertJsonMissingPath('data.password');

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->makeUser();

        $this->postJson('/api/login', ['username' => 'tester', 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'INVALID_CREDENTIALS');

        $this->assertGuest('web');
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $this->makeUser(['status' => 'inactive']);

        $this->postJson('/api/login', ['username' => 'tester', 'password' => 'secret-pass-1'])
            ->assertForbidden()
            ->assertJsonPath('message', 'INACTIVE');

        $this->assertGuest('web');
    }

    public function test_login_is_limited_after_five_wrong_attempts(): void
    {
        $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['username' => 'tester', 'password' => 'wrong'])
                ->assertStatus(422);
        }

        // the sixth try is blocked, even with the right password
        $this->postJson('/api/login', ['username' => 'tester', 'password' => 'secret-pass-1'])
            ->assertStatus(429);
    }

    public function test_current_user_needs_a_login(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();

        $user = $this->makeUser();

        $this->actingAs($user, 'web')->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.username', 'tester');
    }

    public function test_user_can_log_out(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'web')->postJson('/api/logout')->assertNoContent();

        $this->assertGuest('web');
    }

    public function test_deactivated_account_loses_access(): void
    {
        $user = $this->makeUser(['status' => 'inactive']);

        $this->actingAs($user, 'web')->getJson('/api/user')
            ->assertForbidden()
            ->assertJsonPath('message', 'INACTIVE');
    }
}