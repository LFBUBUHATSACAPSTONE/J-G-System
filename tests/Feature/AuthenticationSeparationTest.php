<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Cookie\QueueingFactory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The two sides of the system keep their own session.
 *
 * The customer area (landing page, booking, account) and the admin panel (/admin) used to share
 * ONE session, so signing in on one side threw the other side out and both sides shared the same
 * "remember me" cookie. They now have their own session (`web` and `admin`), and this test file
 * is what keeps them apart:
 *
 *   - an admin and a customer can be signed in at the same time;
 *   - each of them lands on their OWN home page, profile page and sign-out page;
 *   - signing out on one side never signs the other side out (unless it is the SAME account);
 *   - the two sides use their own "remember me" cookie.
 */
class AuthenticationSeparationTest extends TestCase
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

    private function customer(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'customer@example.com',
            'password' => 'secret-password',
        ], $overrides));
    }

    private function admin(array $overrides = []): User
    {
        return User::factory()->admin()->create(array_merge([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ], $overrides));
    }

    /** Signs the customer in through the landing page modal. */
    private function signInCustomer(User $user): void
    {
        $this->postJson(route('login'), [
            'identifier' => $user->email,
            'password' => 'secret-password',
        ])->assertOk();
    }

    /** Signs the admin in through the panel's sign-in card. */
    private function signInAdmin(User $user): void
    {
        $this->postJson(route('admin.login.submit'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertOk();
    }

    // --------------------------------------------------- signing in side by side

    public function test_an_admin_and_a_customer_can_be_signed_in_at_the_same_time(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->signInCustomer($customer);
        $this->signInAdmin($admin);

        // The customer session was not thrown out by the admin signing in.
        $this->assertAuthenticatedAs($customer);

        // The panel has its own session, with the admin in it.
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertSame($admin->id, Auth::guard('admin')->id());
    }

    public function test_the_admin_panel_still_opens_for_an_admin_who_is_signed_in_on_the_customer_session(): void
    {
        $admin = $this->admin();

        // The way the tests (and the admin sign-in card) sign an admin in: on the customer guard.
        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_the_customer_area_still_opens_for_a_customer_who_is_signed_in_on_the_customer_session(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer);

        $this->get(route('user.account.profile'))->assertOk();
    }

    // ------------------------------------------------------------- the home page

    public function test_the_home_page_sends_a_guest_to_the_landing_page(): void
    {
        $this->get('/')->assertRedirect(route('user.landing'));
    }

    public function test_the_home_page_sends_a_customer_to_the_landing_page(): void
    {
        $this->signInCustomer($this->customer());

        $this->get('/')->assertRedirect(route('user.landing'));
    }

    public function test_the_home_page_sends_an_admin_to_the_admin_dashboard(): void
    {
        $this->signInAdmin($this->admin());

        $this->get('/')->assertRedirect(route('admin.dashboard'));
    }

    public function test_the_home_page_sends_an_admin_to_the_dashboard_even_with_a_customer_signed_in(): void
    {
        $this->signInCustomer($this->customer());
        $this->signInAdmin($this->admin());

        $this->get('/')->assertRedirect(route('admin.dashboard'));
    }

    // ------------------------------------------------------------- the profile page

    public function test_the_account_link_sends_a_customer_to_their_own_profile(): void
    {
        $this->signInCustomer($this->customer());

        $this->get(route('user.account'))->assertRedirect(route('user.account.profile'));
    }

    public function test_the_account_link_sends_an_admin_to_the_admin_account_page(): void
    {
        $this->signInAdmin($this->admin());

        $this->get(route('user.account'))->assertRedirect(route('admin.account'));
    }

    public function test_the_account_link_of_a_guest_goes_to_the_landing_page(): void
    {
        $this->get(route('user.account'))->assertRedirect(route('user.landing'));
    }

    // ------------------------------------------------------------- signing out

    public function test_a_customer_signs_out_to_the_landing_page(): void
    {
        $this->signInCustomer($this->customer());

        $this->postJson(route('logout'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', route('user.landing'));

        $this->assertGuest();
    }

    public function test_an_admin_signs_out_of_the_panel_to_the_sign_in_card(): void
    {
        $this->signInAdmin($this->admin());

        $this->postJson(route('admin.logout'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', route('admin.login'));

        $this->assertGuest();
    }

    public function test_a_customer_signing_out_leaves_the_admin_session_alone(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->signInCustomer($customer);
        $this->signInAdmin($admin);

        $this->postJson(route('logout'))->assertOk();

        // The customer is gone...
        $this->assertGuest();

        // ...but the panel session is still there.
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertSame($admin->id, Auth::guard('admin')->id());
    }

    public function test_an_admin_signing_out_of_the_panel_leaves_the_customer_session_alone(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->signInCustomer($customer);
        $this->signInAdmin($admin);

        $this->postJson(route('admin.logout'))->assertOk();

        // The panel session is gone...
        $this->assertFalse(Auth::guard('admin')->check());

        // ...but the customer session is still there.
        $this->assertAuthenticatedAs($customer);
    }

    public function test_an_admin_who_signs_out_through_the_customer_form_goes_to_the_sign_in_card(): void
    {
        $admin = $this->admin();

        $this->signInAdmin($admin);

        $this->postJson(route('logout'))
            ->assertOk()
            ->assertJsonPath('redirect', route('admin.login'));
    }

    public function test_signing_out_of_the_customer_side_ends_the_same_account_on_the_panel(): void
    {
        $admin = $this->admin();

        $this->signInAdmin($admin);

        // The same person asking to be signed out is signed out everywhere.
        $this->postJson(route('logout'))->assertOk();

        $this->assertGuest();
        $this->assertFalse(Auth::guard('admin')->check());
    }

    // ----------------------------------------------------------- the sign-in pages

    public function test_a_guest_is_sent_to_the_sign_in_card_of_the_side_it_was_trying_to_reach(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('user.account.profile'))->assertRedirect(route('user.landing'));
    }

    public function test_a_signed_in_admin_is_sent_to_the_dashboard_instead_of_the_sign_in_card(): void
    {
        $this->signInAdmin($this->admin());

        $this->get(route('admin.login'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_signed_in_customer_is_sent_to_the_landing_page_instead_of_the_sign_in_card(): void
    {
        $this->signInCustomer($this->customer());

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee("doesn't have admin access", false);
    }

    // ---------------------------------------------------------- the remember cookie

    public function test_the_two_sides_use_their_own_remember_me_cookie(): void
    {
        $customer = $this->customer(['email' => 'remember-customer@example.com']);
        $admin = $this->admin(['email' => 'remember-admin@example.com']);

        $customerCookies = $this->rememberCookiesFor(fn () => $this->postJson(route('login'), [
            'identifier' => $customer->email,
            'password' => 'secret-password',
            'remember' => true,
        ])->assertOk());

        $adminCookies = $this->rememberCookiesFor(fn () => $this->postJson(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'secret-password',
            'remember' => true,
        ])->assertOk());

        $this->assertNotEmpty($customerCookies, 'The customer sign-in set no remember cookie.');
        $this->assertNotEmpty($adminCookies, 'The admin sign-in set no remember cookie.');

        // The two sides never share a cookie.
        $this->assertEmpty(array_intersect($customerCookies, $adminCookies));

        // Each one uses the cookie of its own guard.
        $this->assertTrue($this->hasCookieStartingWith($customerCookies, 'remember_web_'));
        $this->assertTrue($this->hasCookieStartingWith($adminCookies, 'remember_admin_'));
        $this->assertFalse($this->hasCookieStartingWith($adminCookies, 'remember_web_'));
    }

    /**
     * The names of the "remember me" cookies that one request queued.
     *
     * The cookie jar is a singleton that lives as long as the test does, and Laravel does not
     * empty its queue between requests, so the queue is emptied here first: the cookies of THIS
     * request are the ones under test.
     *
     * @return array<int, string>
     */
    private function rememberCookiesFor(callable $request): array
    {
        $this->app->make(QueueingFactory::class)->flushQueuedCookies();

        $request();

        return collect($this->app->make(QueueingFactory::class)->getQueuedCookies())
            ->map(fn ($cookie) => $cookie->getName())
            ->filter(fn (string $name) => str_starts_with($name, 'remember'))
            ->values()
            ->all();
    }

    /** @param  array<int, string>  $names */
    private function hasCookieStartingWith(array $names, string $prefix): bool
    {
        foreach ($names as $name) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
