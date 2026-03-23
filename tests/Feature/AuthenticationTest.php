<?php

namespace Tests\Feature;

use App\Models\User;
use Coderflex\LaravelTurnstile\Facades\LaravelTurnstile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-04-25 10:00:00');
    }

    // --- Registration ---

    public function test_register_page_is_accessible(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_register_with_valid_data(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Mario',
            'surname' => 'Rossi',
            'birth_date' => '1990-05-15',
            'email' => 'mario@test.it',
            'phone' => '3331234567',
            'address' => 'Via Roma 1',
            'city' => 'Bologna',
            'province' => 'BO',
            'cap' => '40100',
            'password' => 'Password1A',
            'password_confirmation' => 'Password1A',
            'privacy_consent' => '1',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['email' => 'mario@test.it']);
    }

    public function test_register_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'mario@test.it']);

        $response = $this->post(route('register'), [
            'name' => 'Mario',
            'surname' => 'Rossi',
            'birth_date' => '1990-05-15',
            'email' => 'mario@test.it',
            'phone' => '3331234567',
            'address' => 'Via Roma 1',
            'city' => 'Bologna',
            'province' => 'BO',
            'cap' => '40100',
            'password' => 'Password1A',
            'password_confirmation' => 'Password1A',
            'privacy_consent' => '1',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_register_minor_rejected(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Mario',
            'surname' => 'Rossi',
            'birth_date' => now()->subYears(16)->format('Y-m-d'),
            'email' => 'minor@test.it',
            'phone' => '3331234567',
            'address' => 'Via Roma 1',
            'city' => 'Bologna',
            'province' => 'BO',
            'cap' => '40100',
            'password' => 'Password1A',
            'password_confirmation' => 'Password1A',
            'privacy_consent' => '1',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertSessionHasErrors('birth_date');
        $this->assertDatabaseMissing('users', ['email' => 'minor@test.it']);
    }

    // --- Login ---

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'Password1A']);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password1A',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertRedirect(route('game.show'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'Password1A']);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'WrongPassword1',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_unverified_shows_error_and_stays_guest(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'Password1A']);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password1A',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // --- Auth guards ---

    public function test_game_page_requires_authentication(): void
    {
        $response = $this->get(route('game.show'));

        $response->assertRedirect(route('login'));
    }

    public function test_game_page_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('game.show'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    // --- Authenticated user cannot access guest routes ---

    public function test_authenticated_user_cannot_access_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect('/');
    }

    // --- Password Reset ---

    public function test_password_reset_request_page_is_accessible(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
    }

    public function test_password_reset_sends_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_password_reset_with_invalid_email(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@test.it',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_form_page_is_accessible(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        $response->assertStatus(200);
    }

    public function test_password_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword1A',
            'password_confirmation' => 'NewPassword1A',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword1A', $user->password));
    }

    public function test_password_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPassword1A',
            'password_confirmation' => 'NewPassword1A',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertSessionHasErrors('email');
    }

    // --- Turnstile ---

    public function test_login_fails_with_invalid_turnstile(): void
    {
        LaravelTurnstile::shouldReceive('validate')
            ->once()
            ->andReturn(['success' => false]);

        $user = User::factory()->create(['password' => 'Password1A']);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password1A',
            'cf-turnstile-response' => 'invalid-token',
        ]);

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_register_fails_with_invalid_turnstile(): void
    {
        LaravelTurnstile::shouldReceive('validate')
            ->once()
            ->andReturn(['success' => false]);

        $response = $this->post(route('register'), [
            'name' => 'Mario',
            'surname' => 'Rossi',
            'birth_date' => '1990-05-15',
            'email' => 'turnstile@test.it',
            'phone' => '3331234567',
            'address' => 'Via Roma 1',
            'city' => 'Bologna',
            'province' => 'BO',
            'cap' => '40100',
            'password' => 'Password1A',
            'password_confirmation' => 'Password1A',
            'privacy_consent' => '1',
            'cf-turnstile-response' => 'invalid-token',
        ]);

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertDatabaseMissing('users', ['email' => 'turnstile@test.it']);
    }
}
