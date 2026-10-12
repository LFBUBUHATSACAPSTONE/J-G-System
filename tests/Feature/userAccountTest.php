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

  public function test_security_page_renders_the_password_form_and_sign_in_methods(): void
  {
    $this->withoutVite();

    $this->get(route('user.account.security'))
      ->assertOk()
      ->assertSee('id="account-password-form"', false)
      ->assertSee('name="current_password"', false)
      ->assertSee('name="password_confirmation"', false)
      ->assertSee('Sign-in methods')
      ->assertSee('Google');
  }

  public function test_password_update_stub_answers_ok_and_saves_nothing(): void
  {
    $this->patchJson(route('user.account.password.update'), [
      'current_password' => 'Old-pass1!',
      'password' => 'New-pass1!',
      'password_confirmation' => 'New-pass1!',
    ])->assertOk()->assertJsonPath('ok', true);
  }

  public function test_delete_account_tab_opens_the_confirmation_modal(): void
  {
    $this->withoutVite();

    // The tab is a button for the modal (no route), and the modal renders on every account page.
    // With no `user.account.destroy` route yet, the modal's delete URL is empty: nothing is sent.
    $this->get(route('user.account.profile'))
      ->assertOk()
      ->assertSee('data-bs-target="#deleteAccountModal"', false)
      ->assertSee('id="deleteAccountModal"', false)
      ->assertSee('data-delete-url=""', false)
      ->assertSee('Type <strong>DELETE</strong> to confirm', false);
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

  public function test_messages_page_renders_the_shared_chat(): void
  {
    $this->withoutVite();

    // Guests see the chat shell (empty thread + composer). The thread itself is
    // filled from the logged-in user's real conversation once the backend is
    // connected, so no sample reference or unread badge is rendered here.
    $this->get(route('user.account.messages'))
      ->assertOk()
      ->assertSee('data-account-chat', false)
      ->assertSee('data-chat-form', false)
      ->assertSee(route('user.messages.send'), false)
      ->assertSee('Ask us about packages or your booking', false);
  }
}
