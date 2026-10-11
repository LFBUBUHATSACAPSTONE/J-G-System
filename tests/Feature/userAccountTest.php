<?php

namespace Tests\Feature;

use Tests\TestCase;

// Front-end stubs for the user account section (routes/user/account.php). No database and no login
// are needed: the pages fall back to sample values and the profile PATCH only answers 200.
class UserAccountTest extends TestCase
{
  public function test_account_redirects_to_the_profile_tab(): void
  {
    $this->get(route('user.account'))->assertRedirect(route('user.account.profile'));
  }

  public function test_profile_page_renders_with_the_sidebar_tabs(): void
  {
    $this->withoutVite();

    $this->get(route('user.account.profile'))
      ->assertOk()
      ->assertSee('id="account-profile-form"', false)
      ->assertSee('Back to Home')
      ->assertSee('My Bookings')
      ->assertSee('Delete Account')
      ->assertSee('Log out');
  }

  public function test_profile_update_stub_answers_ok(): void
  {
    $this->patchJson(route('user.account.profile.update'), [
      'first_name' => 'Juan',
      'last_name' => 'Dela Cruz',
    ])->assertOk()->assertJsonPath('ok', true);
  }

  public function test_bookings_page_renders_the_sample_bookings_and_tabs(): void
  {
    $this->withoutVite();

    $this->get(route('user.account.bookings'))
      ->assertOk()
      ->assertSee('data-account-bookings', false)
      ->assertSee('My Bookings')
      ->assertSee('Upcoming')
      ->assertSee('Past')
      ->assertSee('Cancelled')
      ->assertSee('#JG12345')
      ->assertSee('Confirmed')
      ->assertSee('Cancelled by you')
      ->assertSee('Reschedule');
  }

  public function test_bookings_tab_is_a_live_link_in_the_sidebar(): void
  {
    $this->withoutVite();

    // A tab whose route exists is a link; one without a route is drawn with a "Soon" tag.
    $html = $this->get(route('user.account.bookings'))->getContent();

    $this->assertStringContainsString('href="' . route('user.account.bookings') . '"', $html);
    $this->assertStringContainsString('aria-current="page"', $html);
  }

  public function test_cancel_stub_answers_ok_and_saves_nothing(): void
  {
    $this->postJson(route('user.account.bookings.cancel', ['booking' => 1]))
      ->assertOk()
      ->assertJsonPath('ok', true)
      ->assertJsonPath('booking.status', 'user_cancelled');
  }
}
