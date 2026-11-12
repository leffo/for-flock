<?php

namespace Tests\Feature;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_creates_an_account_and_starts_a_session(): void
    {
        Mail::fake();

        $this->postJson(route('register.store'), [
            'name' => 'Test User',
            'email' => 'new-user@example.test',
            'password' => 'example-password',
            'password_confirmation' => 'example-password',
        ])->assertCreated();

        $user = User::where('email', 'new-user@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertTrue(Hash::check('example-password', $user->password));

        Mail::assertSent(WelcomeEmail::class, fn (WelcomeEmail $mail): bool => $mail->hasTo($user->email));
        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'welcome_email_sent' => true,
        ]);

        Auth::forgetGuards();

        $this->getJson(route('api.user'))
            ->assertOk()
            ->assertJsonPath('data.welcome_email_sent', true);
    }

    public function test_registration_succeeds_when_the_welcome_email_cannot_be_sent(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->with(Mockery::type(User::class))
            ->andThrow(new RuntimeException('Mail transport unavailable.'));

        $this->postJson(route('register.store'), [
            'name' => 'Test User',
            'email' => 'new-user@example.test',
            'password' => 'example-password',
            'password_confirmation' => 'example-password',
        ])->assertCreated();

        $this->assertDatabaseHas(User::class, [
            'email' => 'new-user@example.test',
            'welcome_email_sent' => false,
        ]);
    }

    public function test_welcome_email_greets_the_registered_user(): void
    {
        $welcomeEmail = new WelcomeEmail(User::factory()->make(['name' => 'Test User']));

        $welcomeEmail->assertSeeInHtml('Здравствуйте, Test User!');
    }

    public function test_registration_rejects_an_unconfirmed_password(): void
    {
        $this->postJson(route('register.store'), [
            'name' => 'Test User',
            'email' => 'new-user@example.test',
            'password' => 'example-password',
            'password_confirmation' => 'different-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertGuest('web');
        $this->assertDatabaseMissing(User::class, ['email' => 'new-user@example.test']);
    }

    public function test_login_session_can_access_the_sanctum_api(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        Auth::forgetGuards();

        $this->withCredentials()
            ->withUnencryptedCookie(
                config('session.cookie'),
                $response->getCookie(config('session.cookie'), decrypt: false)->getValue(),
            )
            ->withHeader('Referer', route('login'))
            ->getJson(route('api.user'))
            ->assertOk()
            ->assertExactJson(['data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'welcome_email_sent' => false,
            ]]);
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->postJson(route('logout'))->assertNoContent();

        $this->assertGuest('web');
    }

    public function test_password_recovery_sends_a_link_to_the_spa(): void
    {
        $user = User::factory()->create();
        Notification::fake();

        $this->postJson(route('password.email'), ['email' => $user->email])->assertOk();

        Notification::assertSentTo($user, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;

            $this->withoutVite()->get($url)->assertOk()->assertViewIs('app');

            return true;
        });
    }

    public function test_password_reset_with_a_valid_token_updates_the_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->postJson(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-example-password',
            'password_confirmation' => 'new-example-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-example-password', $user->refresh()->password));
        $this->assertFalse(Password::tokenExists($user, $token));
    }

    public function test_password_reset_with_an_invalid_token_keeps_the_old_password(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('password.update'), [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'new-example-password',
            'password_confirmation' => 'new-example-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }
}
