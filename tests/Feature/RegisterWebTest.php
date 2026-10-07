<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class RegisterWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_route_creates_user_only_after_code_verification(): void
    {
        Mail::fake();

        $response = $this->postJson('/register', [
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'identifier' => 'ana@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'terms' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'destination',
                'verification_token',
            ]);

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);

        $code = null;
        Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return strlen($mail->code) === 6 && ctype_digit($mail->code);
        });

        $response = $this->postJson('/verification/confirm', [
            'code' => $code,
            'context' => 'signup',
            'verification_token' => $response->json('verification_token'),
        ]);

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseHas('users', [
            'email' => 'ana@example.com',
            'name' => 'Ana Cruz',
        ]);
        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('StrongPass1!', $user->password));
    }

    public function test_invalid_signup_code_does_not_create_user(): void
    {
        Mail::fake();

        $signup = $this->postJson('/register', [
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'identifier' => 'ana@example.com',
            'password' => 'StrongPass1!',
            'terms' => true,
        ]);

        $signup->assertStatus(201);

        $confirmation = $this->postJson('/verification/confirm', [
            'code' => '000000',
            'context' => 'signup',
            'verification_token' => $signup->json('verification_token'),
        ]);

        $confirmation->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    public function test_resending_signup_code_keeps_account_pending_until_latest_code_is_confirmed(): void
    {
        Mail::fake();

        $signup = $this->postJson('/register', [
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'identifier' => 'ana@example.com',
            'password' => 'StrongPass1!',
            'terms' => true,
        ]);
        $signup->assertStatus(201);

        $firstCode = Mail::sent(VerificationCodeMail::class)->first()->code;
        $resend = $this->postJson('/verification/resend', [
            'context' => 'signup',
            'verification_token' => $signup->json('verification_token'),
        ]);

        $resend->assertOk();
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
        $this->assertSame(2, Mail::sent(VerificationCodeMail::class)->count());
        $latestCode = Mail::sent(VerificationCodeMail::class)->last()->code;

        $oldCodeResponse = $this->postJson('/verification/confirm', [
            'code' => $firstCode,
            'context' => 'signup',
            'verification_token' => $signup->json('verification_token'),
        ]);
        $oldCodeResponse->assertStatus(422);

        $newCodeResponse = $this->postJson('/verification/confirm', [
            'code' => $latestCode,
            'context' => 'signup',
            'verification_token' => $resend->json('verification_token'),
        ]);

        $newCodeResponse->assertOk()->assertJsonPath('ok', true);
        $this->assertNotNull(User::where('email', 'ana@example.com')->value('email_verified_at'));
    }

    public function test_login_and_password_reset_modal_flow_works(): void
    {
        Mail::fake();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '09123456789',
            'password' => Hash::make('password123'),
        ]);

        $loginResponse = $this->postJson('/login', [
            'identifier' => 'test@example.com',
            'password' => 'password123',
            'remember' => false,
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', route('user.landing'));

        $this->assertAuthenticated();
        $this->get(route('user.landing'))
            ->assertOk()
            ->assertSee('data-chat-open', false)
            ->assertSee('aria-label="Profile"', false);

        $forgotResponse = $this->postJson('/password/email', [
            'identifier' => 'test@example.com',
        ]);

        $forgotResponse->assertOk()
            ->assertJsonStructure([
                'destination',
                'verification_token',
            ]);

        $token = $forgotResponse->json('verification_token');
        $code = null;

        Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $confirmResponse = $this->postJson('/verification/confirm', [
            'code' => $code,
            'context' => 'reset',
            'verification_token' => $token,
        ]);

        $confirmResponse->assertOk()
            ->assertJsonStructure([
                'token',
                'email',
            ]);

        $newToken = $confirmResponse->json('token');
        $email = $confirmResponse->json('email');

        $passwordUpdate = $this->postJson('/password/update', [
            'token' => $newToken,
            'email' => $email,
            'password' => 'NewStrongPass1!',
            'password_confirmation' => 'NewStrongPass1!',
        ]);

        $passwordUpdate->assertOk()->assertJsonPath('ok', true);

        $this->assertTrue(Hash::check('NewStrongPass1!', User::first()->password));
    }

    public function test_signup_form_does_not_require_password_confirmation_field(): void
    {
        $response = $this->get('/user/landing');

        $response->assertOk()
            ->assertDontSee('name="password_confirmation"');
    }

    public function test_verification_form_has_hidden_verification_token_field(): void
    {
        $response = $this->get('/user/landing');

        $response->assertOk()
            ->assertSee('name="verification_token"', false);
    }

    public function test_signup_verification_requires_a_real_token(): void
    {
        $response = $this->postJson('/verification/confirm', [
            'code' => '123456',
            'context' => 'signup',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('verification_token');
    }

    public function test_unverified_signup_can_request_a_new_code(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'pending@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/register', [
            'first_name' => 'Pending',
            'last_name' => 'User',
            'identifier' => 'pending@example.com',
            'password' => 'NewStrongPass1!',
            'terms' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['destination', 'verification_token']);

        Mail::assertSent(VerificationCodeMail::class);
    }

    public function test_verified_email_cannot_register_again(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/register', [
            'first_name' => 'Existing',
            'last_name' => 'User',
            'identifier' => 'verified@example.com',
            'password' => 'StrongPass1!',
            'terms' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'An account with this email is already verified. Please log in or use Forgot Password.');

        Mail::assertNothingOutgoing();
    }

    public function test_landing_header_shows_messages_and_profile_icons_to_authenticated_users(): void
    {
        $this->get(route('user.landing'))
            ->assertOk()
            ->assertDontSee('data-chat-open', false)
            ->assertSee('data-auth-view="signup"', false)
            ->assertSee('data-auth-view="login"', false)
            ->assertSee('class="rounded-3 btn-color-gradient--primary px-3 py-2"', false)
            ->assertSee('class="rounded-3 btn-color-gradient--secondary px-3 py-2"', false)
            ->assertSee('id="landing-book-now" data-bs-toggle="modal"', false)
            ->assertSee('class="package-card__cta" data-package-select data-bs-toggle="modal"', false);

        $this->actingAs(User::factory()->create())
            ->get(route('user.landing'))
            ->assertOk()
            ->assertSee('data-chat-open', false)
            ->assertSee('aria-label="Profile"', false)
            ->assertSee('href="' . route('home') . '"', false)
            ->assertSee('id="landing-book-now" href="' . route('user.booking') . '"', false)
            ->assertDontSee('id="landing-book-now" data-bs-toggle="modal"', false)
            ->assertSee('class="package-card__cta" data-package-select href="' . route('user.booking') . '"', false)
            ->assertDontSee('class="package-card__cta" data-package-select data-bs-toggle="modal"', false);
    }

    public function test_google_callback_creates_and_authenticates_verified_user(): void
    {
        $googleUser = GoogleUser::fake([
            'id' => 'google-user-123',
            'name' => 'Google User',
            'email' => 'google-user@example.com',
            'verified_email' => true,
        ]);
        $provider = \Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->withCookie('google_oauth_state', 'test-oauth-state')
            ->get('/auth/google/callback?state=test-oauth-state');

        $response->assertRedirect(route('user.landing'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'google-user@example.com',
            'google_id' => 'google-user-123',
        ]);
        $this->assertNotNull(User::where('email', 'google-user@example.com')->value('email_verified_at'));
    }

    public function test_google_redirect_stores_oauth_state_in_cookie(): void
    {
        $response = $this->get('/auth/google/redirect');
        $redirectQuery = [];
        parse_str(parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);

        $response->assertRedirect()
            ->assertCookie('google_oauth_state', $redirectQuery['state'] ?? null);
        $this->assertNotEmpty($redirectQuery['state'] ?? null);
    }

}
