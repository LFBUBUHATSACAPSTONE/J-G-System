<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// The user account backend (Profile, My Bookings, Security, Delete Account, Log out). Uses its own
// in-memory SQLite tables, like BookingFlowTest, so it does not depend on the migration chain.
class UserAccountTest extends TestCase
{
    private const PASSWORD = 'Old-pass1!';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::connection('sqlite')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('google_id')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->text('address')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('sqlite')->create('packages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->boolean('available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
        });
        Package::create(['id' => 'budget-party', 'name' => 'Budget Party', 'price' => 10000, 'available' => true, 'sort_order' => 1, 'features' => []]);

        Schema::connection('sqlite')->create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('reference')->unique();
            $table->string('status')->index();
            $table->string('client_name');
            $table->string('client_email')->index();
            $table->string('client_phone')->nullable();
            $table->text('client_address')->nullable();
            $table->string('package_id')->nullable()->index();
            $table->unsignedBigInteger('rescheduled_from_id')->nullable()->unique();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::connection('sqlite')->create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->unique();
            $table->string('event_name');
            $table->string('event_type');
            $table->string('event_type_other')->nullable();
            $table->string('event_location');
            $table->string('event_contact_person')->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->string('venue_type')->nullable();
            $table->timestamps();
        });
        Schema::connection('sqlite')->create('booking_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->unique();
            $table->date('event_start_date')->index();
            $table->date('event_end_date');
            $table->string('event_start_time')->nullable();
            $table->string('event_end_time')->nullable();
            $table->timestamps();
        });
        Schema::connection('sqlite')->create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->unique();
            $table->string('payment_method')->nullable();
            $table->string('payment_state')->nullable()->index();
            $table->string('payment_reference')->nullable();
            $table->string('payment_receipt_path')->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    // ---------------------------------------------------------------- access

    public function test_guests_get_401_json_and_a_redirect_to_the_landing_page(): void
    {
        $this->getJson(route('user.account.profile'))->assertUnauthorized();
        $this->patchJson(route('user.account.profile.update'), [])->assertUnauthorized();
        $this->getJson(route('user.account.bookings'))->assertUnauthorized();
        $this->postJson(route('user.account.bookings.cancel', ['booking' => 1]))->assertUnauthorized();
        $this->patchJson(route('user.account.password.update'), [])->assertUnauthorized();
        $this->deleteJson(route('user.account.destroy'))->assertUnauthorized();

        $this->get(route('user.account.profile'))->assertRedirect(route('user.landing'));
    }

    public function test_account_redirects_to_the_profile_tab(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('user.account'))
            ->assertRedirect(route('user.account.profile'));
    }

    public function test_the_sidebar_links_to_live_tabs_and_marks_the_current_one(): void
    {
        $this->withoutVite();

        $html = $this->actingAs($this->makeUser())->get(route('user.account.bookings'))->getContent();

        $this->assertStringContainsString('href="'.route('user.account.bookings').'"', $html);
        $this->assertStringContainsString('href="'.route('user.account.security').'"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function test_messages_page_still_renders_for_a_logged_in_user(): void
    {
        $this->withoutVite();

        $this->actingAs($this->makeUser())->get(route('user.account.messages'))
            ->assertOk()
            ->assertSee('data-account-chat', false)
            ->assertSee(route('user.messages.send'), false);
    }

    // --------------------------------------------------------------- profile

<<<<<<< HEAD
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
=======
    public function test_profile_page_renders_the_users_own_details_and_the_sidebar_tabs(): void
    {
        $this->withoutVite();
        $user = $this->makeUser(['first_name' => 'Maria', 'address' => 'San Ildefonso, Bulacan']);

        $this->actingAs($user)->get(route('user.account.profile'))
            ->assertOk()
            ->assertSee('id="account-profile-form"', false)
            ->assertSee('value="Maria"', false)
            ->assertSee('San Ildefonso, Bulacan')
            ->assertSee('Back to Home')
            ->assertSee('My Bookings')
            ->assertSee('Delete Account')
            ->assertSee('Log out')
            ->assertSee('data-account-logout', false);
    }

    public function test_profile_is_returned_as_json_when_the_request_asks_for_json(): void
    {
        $user = $this->makeUser(['phone' => '09171234567']);

        $this->actingAs($user)->getJson(route('user.account.profile'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('profile.email', $user->email)
            ->assertJsonPath('profile.phone', '09171234567')
            ->assertJsonPath('profile.email_verified', true);
    }

    public function test_profile_update_saves_the_fields_and_never_changes_the_email(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->patchJson(route('user.account.profile.update'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'phone' => '09181234567',
            'address' => 'Brgy. Poblacion, San Rafael, Bulacan',
            'email' => 'someone-else@example.com',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('profile.first_name', 'Juan')
            ->assertJsonPath('profile.email', $user->email);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'phone' => '09181234567',
            'address' => 'Brgy. Poblacion, San Rafael, Bulacan',
            'email' => $user->email,
        ]);
    }

    public function test_profile_update_clears_optional_fields_when_they_are_sent_empty(): void
    {
        $user = $this->makeUser(['phone' => '09171234567', 'address' => 'Somewhere']);

        $this->actingAs($user)->patchJson(route('user.account.profile.update'), [
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'phone' => '',
            'address' => '',
        ])->assertOk();

        $user->refresh();
        $this->assertNull($user->phone);
        $this->assertNull($user->address);
    }

    public function test_profile_update_rejects_bad_input_with_422_errors(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->patchJson(route('user.account.profile.update'), [
            'first_name' => '',
            'last_name' => 'Cruz3',
            'phone' => '12345',
            'address' => "line one\nline two",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'phone', 'address']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'first_name' => $user->first_name]);
    }

    public function test_profile_update_rejects_a_phone_number_used_by_another_account(): void
    {
        $this->makeUser(['phone' => '09171234567']);
        $user = $this->makeUser();

        $this->actingAs($user)->patchJson(route('user.account.profile.update'), [
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'phone' => '09171234567',
        ])->assertUnprocessable()->assertJsonValidationErrors('phone');
    }

    public function test_a_user_can_keep_their_own_phone_number_when_saving(): void
    {
        $user = $this->makeUser(['phone' => '09171234567']);

        $this->actingAs($user)->patchJson(route('user.account.profile.update'), [
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'phone' => '09171234567',
        ])->assertOk();
    }

    // -------------------------------------------------------------- bookings

    public function test_my_bookings_lists_only_the_logged_in_users_bookings(): void
    {
        $this->withoutVite();
        $user = $this->makeUser();
        $other = $this->makeUser();
        $this->makeBooking($user, ['reference' => 'JG11111', 'status' => 'approved']);
        $this->makeBooking($other, ['reference' => 'JG22222']);
        $this->makeBooking(null, ['reference' => 'JG33333']);

        $this->actingAs($user)->get(route('user.account.bookings'))
            ->assertOk()
            ->assertSee('data-account-bookings', false)
            ->assertSee('#JG11111')
            ->assertSee('Confirmed')
            ->assertDontSee('JG22222')
            ->assertDontSee('JG33333');

        $this->actingAs($user)->getJson(route('user.account.bookings'))
            ->assertOk()
            ->assertJsonCount(1, 'bookings')
            ->assertJsonPath('bookings.0.reference', '#JG11111')
            ->assertJsonPath('bookings.0.package.name', 'Budget Party')
            ->assertJsonPath('bookings.0.payment.method', 'down')
            ->assertJsonPath('bookings.0.payment.amount', 3000.0)
            ->assertJsonMissingPath('bookings.0.client');
    }

    public function test_my_bookings_shows_reschedule_only_while_it_is_allowed(): void
    {
        $user = $this->makeUser();
        $open = $this->makeBooking($user, ['reference' => 'JG44441', 'status' => 'user_cancelled'], 40);
        $used = $this->makeBooking($user, ['reference' => 'JG44442', 'status' => 'user_cancelled'], 41);
        $this->makeBooking($user, ['reference' => 'JG44443', 'status' => 'pending', 'rescheduled_from_id' => $used->id], 42);
        $approved = $this->makeBooking($user, ['reference' => 'JG44444', 'status' => 'approved'], 43);

        $rows = collect($this->actingAs($user)->getJson(route('user.account.bookings'))->json('bookings'))->keyBy('id');

        $this->assertTrue($rows[$open->id]['can_reschedule']);
        $this->assertNull($rows[$open->id]['reschedule_note']);
        $this->assertFalse($rows[$used->id]['can_reschedule']);
        $this->assertSame('This booking was already rescheduled once.', $rows[$used->id]['reschedule_note']);
        $this->assertFalse($rows[$approved->id]['can_reschedule']);
        $this->assertNull($rows[$approved->id]['reschedule_note']);
    }

    public function test_cancel_sets_the_status_and_keeps_every_row(): void
    {
        $user = $this->makeUser();
        $booking = $this->makeBooking($user, ['status' => 'approved'], 40);

        $this->actingAs($user)->postJson(route('user.account.bookings.cancel', ['booking' => $booking->id]))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('booking.id', $booking->id)
            ->assertJsonPath('booking.status', 'user_cancelled')
            ->assertJsonPath('booking.can_reschedule', true);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'user_cancelled']);
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('booking_events', 1);
        $this->assertDatabaseCount('booking_schedules', 1);
        $this->assertDatabaseCount('booking_payments', 1);
    }

    public function test_cancel_refuses_someone_elses_booking(): void
    {
        $user = $this->makeUser();
        $booking = $this->makeBooking($this->makeUser(), ['status' => 'approved']);

        $this->actingAs($user)->postJson(route('user.account.bookings.cancel', ['booking' => $booking->id]))
            ->assertForbidden();
        $this->actingAs($user)->postJson(route('user.account.bookings.cancel', ['booking' => 9999]))
            ->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'approved']);
    }

    public function test_cancel_refuses_a_status_that_cannot_be_cancelled(): void
    {
        $user = $this->makeUser();

        foreach (['declined', 'completed', 'cancelled', 'user_cancelled'] as $status) {
            $booking = $this->makeBooking($user, ['status' => $status]);

            $this->actingAs($user)->postJson(route('user.account.bookings.cancel', ['booking' => $booking->id]))
                ->assertStatus(409)
                ->assertJsonStructure(['message']);
            $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => $status]);
        }
    }

    public function test_cancel_refuses_an_event_that_has_started(): void
    {
        $user = $this->makeUser();
        $today = $this->makeBooking($user, ['status' => 'approved'], 0);
        $past = $this->makeBooking($user, ['status' => 'approved'], -3);

        foreach ([$today, $past] as $booking) {
            $this->actingAs($user)->postJson(route('user.account.bookings.cancel', ['booking' => $booking->id]))
                ->assertStatus(409);
            $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'approved']);
        }
    }

    public function test_a_booking_made_while_logged_in_is_linked_to_the_account(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $this->get('/user/booking?package=budget-party')->assertOk();
        $this->postJson(route('booking.client-information'), [
            'first_name' => 'Taylor', 'last_name' => 'Jones', 'email' => 'taylor@example.com',
            'contact_number' => '09171234567', 'address' => 'San Ildefonso, Bulacan',
        ])->assertOk();
        $this->postJson(route('booking.event-information'), [
            'event_name' => 'Birthday', 'event_type' => 'Birthday Party', 'event_location' => 'Bulacan',
            'venue_type' => 'Indoor',
        ])->assertOk();
        $date = now()->addDays(10)->toDateString();
        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date, 'event_end_date' => $date, 'start_time' => '6:00 PM', 'end_time' => '10:00 PM',
        ])->assertOk();
        $reference = $this->postJson(route('booking.booking-summary'), ['payment_option' => 'full'])
            ->assertOk()->json('reference');

        $this->assertDatabaseHas('bookings', ['reference' => $reference, 'user_id' => $user->id]);
        $this->getJson(route('user.account.bookings'))->assertJsonCount(1, 'bookings');
    }

    public function test_only_the_owner_can_open_the_reschedule_page_for_a_cancelled_booking(): void
    {
        $this->withoutVite();
        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner, ['status' => 'user_cancelled'], 40);

        $this->actingAs($this->makeUser())->get('/user/booking?reschedule='.$booking->id)->assertForbidden();
        $this->actingAs($owner)->get('/user/booking?reschedule='.$booking->id)->assertOk();
    }

    // -------------------------------------------------------------- security

    public function test_security_page_renders_the_password_form_pointing_at_the_real_route(): void
    {
        $this->withoutVite();

        $this->actingAs($this->makeUser(['google_id' => 'g-123']))->get(route('user.account.security'))
            ->assertOk()
            ->assertSee('id="account-password-form"', false)
            ->assertSee('name="current_password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('action="'.route('user.account.password.update').'"', false)
            ->assertSee('Sign-in methods');
    }

    public function test_security_returns_the_sign_in_methods_as_json(): void
    {
        $this->actingAs($this->makeUser(['google_id' => 'g-123']))->getJson(route('user.account.security'))
            ->assertOk()
            ->assertJsonPath('sign_in_methods.google', true)
            ->assertJsonPath('sign_in_methods.email_password', true);
    }

    public function test_password_update_changes_the_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->patchJson(route('user.account.password.update'), [
            'current_password' => self::PASSWORD,
            'password' => 'New-pass1!',
            'password_confirmation' => 'New-pass1!',
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertTrue(Hash::check('New-pass1!', $user->fresh()->password));
    }

    public function test_password_update_rejects_a_wrong_current_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->patchJson(route('user.account.password.update'), [
            'current_password' => 'Wrong-pass1!',
            'password' => 'New-pass1!',
            'password_confirmation' => 'New-pass1!',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));
    }

    public function test_password_update_enforces_the_same_rule_as_the_form(): void
    {
        $user = $this->makeUser();

        foreach (['short1!A', 'alllowercase1!', 'ALLUPPERCASE1!', 'NoNumbers!!', 'NoSpecial123'] as $weak) {
            $this->actingAs($user)->patchJson(route('user.account.password.update'), [
                'current_password' => self::PASSWORD,
                'password' => $weak,
                'password_confirmation' => $weak,
            ])->assertUnprocessable()->assertJsonValidationErrors('password');
        }

        $this->actingAs($user)->patchJson(route('user.account.password.update'), [
            'current_password' => self::PASSWORD,
            'password' => 'New-pass1!',
            'password_confirmation' => 'Different-pass1!',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));
    }

    public function test_password_update_rejects_the_same_password(): void
    {
        $this->actingAs($this->makeUser())->patchJson(route('user.account.password.update'), [
            'current_password' => self::PASSWORD,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_password_update_is_rate_limited_after_repeated_wrong_current_passwords(): void
    {
        $user = $this->makeUser();
        $payload = [
            'current_password' => 'Wrong-pass1!',
            'password' => 'New-pass1!',
            'password_confirmation' => 'New-pass1!',
        ];

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($user)->patchJson(route('user.account.password.update'), $payload)->assertUnprocessable();
        }

        $this->actingAs($user)->patchJson(route('user.account.password.update'), $payload)->assertStatus(429);
    }

    // ---------------------------------------------------------------- delete

    public function test_delete_modal_points_at_the_real_route(): void
    {
        $this->withoutVite();

        $this->actingAs($this->makeUser())->get(route('user.account.profile'))
            ->assertOk()
            ->assertSee('data-bs-target="#deleteAccountModal"', false)
            ->assertSee('id="deleteAccountModal"', false)
            ->assertSee('data-delete-url="'.route('user.account.destroy').'"', false)
            ->assertSee('Type <strong>DELETE</strong> to confirm', false);
    }

    public function test_delete_is_blocked_while_the_account_has_active_bookings(): void
    {
        $user = $this->makeUser();
        $this->makeBooking($user, ['status' => 'pending'], 20);

        $this->actingAs($user)->deleteJson(route('user.account.destroy'))
            ->assertStatus(409)
            ->assertJsonStructure(['message']);

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_delete_soft_deletes_the_user_logs_out_and_keeps_all_data(): void
    {
        $user = $this->makeUser();
        // None of these block: cancelled, declined, completed, and an approved booking whose event is over.
        $this->makeBooking($user, ['status' => 'user_cancelled'], 20);
        $this->makeBooking($user, ['status' => 'declined'], 21);
        $this->makeBooking($user, ['status' => 'completed'], -10);
        $this->makeBooking($user, ['status' => 'approved'], -4);

        $this->actingAs($user)->deleteJson(route('user.account.destroy'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', route('user.landing'));

        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email, 'first_name' => $user->first_name]);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('bookings', 4);
        $this->assertDatabaseCount('booking_events', 4);
        $this->assertDatabaseCount('booking_schedules', 4);
        $this->assertDatabaseCount('booking_payments', 4);
        $this->assertDatabaseCount('packages', 1);
        $this->assertSame(4, Booking::where('user_id', $user->id)->count());
    }

    public function test_a_deleted_account_cannot_log_in_or_sign_up_again(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->deleteJson(route('user.account.destroy'))->assertOk();

        $this->postJson('/login', ['identifier' => $user->email, 'password' => self::PASSWORD])
            ->assertUnprocessable();
        $this->assertGuest();

        $this->postJson('/register', [
            'first_name' => 'Again', 'last_name' => 'Person', 'identifier' => $user->email,
            'password' => 'Strong-pass1!', 'password_confirmation' => 'Strong-pass1!', 'terms' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    // ---------------------------------------------------------------- logout

    public function test_logout_answers_json_and_ends_the_session(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->postJson(route('logout'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', route('user.landing'));

        $this->assertGuest();
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_logout_is_safe_to_call_when_already_logged_out(): void
    {
        $this->postJson(route('logout'))->assertOk()->assertJsonPath('ok', true);
    }

    // --------------------------------------------------------------- helpers

    private function makeUser(array $overrides = []): User
    {
        static $count = 0;
        $count++;

        $user = new User(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => "user{$count}@example.com",
            'password' => self::PASSWORD,
        ], $overrides));
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    private function makeBooking(?User $user, array $data = [], int $daysAhead = 10): Booking
    {
        static $count = 0;
        $count++;

        $booking = Booking::create([
            'user_id' => $user?->id,
            'reference' => $data['reference'] ?? 'JG'.str_pad((string) (50000 + $count), 5, '0', STR_PAD_LEFT),
            'status' => $data['status'] ?? 'pending',
            'client_name' => 'Test User',
            'client_email' => $user?->email ?? 'guest@example.com',
            'client_phone' => '09171234567',
            'client_address' => 'Test Address',
            'package_id' => 'budget-party',
            'rescheduled_from_id' => $data['rescheduled_from_id'] ?? null,
            'submitted_at' => now()->subMinutes($count),
        ]);

        $date = now()->addDays($daysAhead)->toDateString();
        $booking->eventDetails()->create([
            'event_name' => 'Test Event',
            'event_type' => 'Wedding',
            'event_location' => 'Test Location',
            'venue_type' => 'Indoor',
        ]);
        $booking->schedule()->create([
            'event_start_date' => $date,
            'event_end_date' => $date,
            'event_start_time' => '6:00 PM',
            'event_end_time' => '10:00 PM',
        ]);
        $booking->payment()->create([
            'payment_method' => 'down',
            'payment_state' => 'partial',
            'payment_amount' => 3000,
        ]);

        return $booking;
    }
>>>>>>> origin
}
