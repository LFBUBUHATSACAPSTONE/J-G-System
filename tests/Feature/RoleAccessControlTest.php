<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Role-based access control for both sides of the system.
 *
 *   user  role -> the customer area only (landing, booking, account, messages)
 *   admin role -> the admin panel only (/admin/*)
 *
 * A guest hitting a guarded page is redirected to the sign-in card for that side; a JSON request
 * gets 401. A signed-in account whose role does not match is refused: JSON gets 403, a page is
 * redirected to where the account belongs.
 */
class RoleAccessControlTest extends TestCase
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

    private function user(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    // ---------------------------------------------------------------- guests

    public function test_a_guest_is_redirected_from_an_admin_page_to_the_sign_in_card(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_a_guest_gets_401_from_an_admin_json_endpoint(): void
    {
        $this->getJson(route('admin.bookings.data'))->assertUnauthorized();
    }

    public function test_a_guest_is_redirected_from_a_user_page_to_the_landing_page(): void
    {
        $this->get(route('user.account.profile'))->assertRedirect(route('user.landing'));
    }

    // ------------------------------------------------------------- user role

    public function test_a_user_can_open_the_customer_area(): void
    {
        $this->actingAs($this->user())
            ->get(route('user.account.profile'))
            ->assertOk();
    }

    public function test_a_user_is_bounced_from_the_admin_panel(): void
    {
        $this->actingAs($this->user())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->actingAs($this->user())
            ->getJson(route('admin.bookings.data'))
            ->assertForbidden();
    }

    public function test_a_user_cannot_post_to_an_admin_action(): void
    {
        $this->actingAs($this->user())
            ->postJson(route('admin.packages.store'), ['id' => 'x', 'name' => 'X', 'price' => 1])
            ->assertForbidden();
    }

    // ------------------------------------------------------------ admin role

    public function test_an_admin_can_open_the_admin_panel(): void
    {
        $this->actingAsAdmin()
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_an_admin_is_bounced_from_the_customer_area(): void
    {
        $this->actingAsAdmin()
            ->get(route('user.account.profile'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_an_admin_gets_403_from_a_user_json_endpoint(): void
    {
        $this->actingAsAdmin();

        $this->getJson(route('user.account.bookings'))->assertForbidden();
    }

    // ------------------------------------------------------------ sign-in gate

    public function test_the_admin_sign_in_card_still_opens_for_a_guest(): void
    {
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_a_signed_in_non_admin_sees_the_no_access_notice(): void
    {
        $this->actingAs($this->user())
            ->get(route('admin.login'))
            ->assertOk()
            ->assertSee("doesn't have admin access", false);
    }

    public function test_the_user_modal_refuses_an_admin_account(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson(route('login'), [
            'identifier' => 'admin@example.com',
            'password' => 'secret-password',
        ])
            ->assertStatus(403)
            ->assertJsonStructure(['message']);

        $this->assertGuest();
    }

    // ---------------------------------------------------------------- logout

    public function test_admin_logout_ends_the_session_and_returns_to_the_sign_in_card(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_logout_answers_json_for_a_fetch_request(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('admin.logout'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', route('admin.login'));

        $this->assertGuest();
    }
}
