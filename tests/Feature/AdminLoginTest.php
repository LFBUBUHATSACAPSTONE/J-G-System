<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Admin sign-in gate (routes/admin/login.php, AdminAuthController).
 *
 * The POST route answers JSON only (contract in resources/js/admin-login.js):
 *   200 {ok, redirect}  |  422 {message, errors}  |  403 {message}  |  429 {message}
 *
 * Who counts as an admin is users.role === 'admin'. Admin accounts are created by the owner;
 * sign-up always produces a 'user', so a regular account is refused with 403.
 */
class AdminLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Artisan::call('migrate', ['--force' => true]);
    }

    private function admin(array $overrides = []): User
    {
        return User::factory()->admin()->create(array_merge([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ], $overrides));
    }

    public function test_login_page_renders_the_sign_in_card(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Welcome Back, Admin!')
            ->assertDontSee("doesn't have admin access");
    }

    public function test_login_page_shows_the_no_access_notice_for_a_signed_in_non_admin(): void
    {
        $user = User::factory()->create(['email' => 'someone@example.com']);

        $this->actingAs($user)
            ->get(route('admin.login'))
            ->assertOk()
            ->assertSee("doesn't have admin access", false);
    }

    public function test_a_signed_in_admin_is_sent_to_the_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_valid_credentials_sign_in_and_answer_with_the_dashboard_redirect(): void
    {
        $user = $this->admin();

        $this->postJson(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_email_lookup_is_case_insensitive(): void
    {
        $this->admin(['email' => 'Admin@Example.com']);

        $this->postJson(route('admin.login.submit'), [
            'email' => 'ADMIN@example.com',
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_wrong_password_is_rejected_with_the_same_message_under_both_fields(): void
    {
        $this->admin();

        $response = $this->postJson(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'not-the-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['email', 'password']])
            ->assertJsonPath('errors.email.0', $response->json('message'))
            ->assertJsonPath('errors.password.0', $response->json('message'));

        $this->assertGuest();
    }

    public function test_unknown_email_is_rejected_the_same_way_as_a_wrong_password(): void
    {
        $this->postJson(route('admin.login.submit'), [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['email', 'password']]);

        $this->assertGuest();
    }

    public function test_missing_fields_return_validation_errors(): void
    {
        $this->postJson(route('admin.login.submit'), [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['email', 'password']])
            ->assertJsonPath('errors.email.0', 'Enter your email address.')
            ->assertJsonPath('errors.password.0', 'Enter a password.');
    }

    public function test_a_non_admin_account_gets_403_and_keeps_its_session(): void
    {
        $user = User::factory()->create([
            'email' => 'someone@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson(route('admin.login.submit'), [
            'email' => 'someone@example.com',
            'password' => 'secret-password',
        ])
            ->assertStatus(403)
            ->assertJsonStructure(['message']);

        // The session is kept on purpose: the sign-in card then shows the "no access" notice.
        $this->assertAuthenticatedAs($user);
    }

    public function test_repeated_failures_are_rate_limited_after_five_attempts(): void
    {
        $this->admin();

        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('admin.login.submit'), [
                'email' => 'admin@example.com',
                'password' => "wrong-{$attempt}",
            ])->assertStatus(422);
        }

        $this->postJson(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-6',
        ])
            ->assertStatus(429)
            ->assertJsonStructure(['message']);

        // Even the RIGHT password is refused while the limit lasts.
        $this->postJson(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_remember_me_sets_the_session_guard_remember_cookie(): void
    {
        $this->admin();

        $response = $this->postJson(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'remember' => true,
        ]);

        $response->assertOk();

        $cookies = collect($response->headers->getCookies());
        $this->assertTrue($cookies->contains(fn ($cookie) => str_starts_with($cookie->getName(), 'remember')));
    }
}
