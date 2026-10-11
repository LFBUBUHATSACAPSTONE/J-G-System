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
}
