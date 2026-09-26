<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_route_creates_user_from_identifier_payload(): void
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

        $this->assertDatabaseHas('users', [
            'email' => 'ana@example.com',
            'name' => 'Ana Cruz',
        ]);

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
            ->assertJsonPath('ok', true);

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

}
