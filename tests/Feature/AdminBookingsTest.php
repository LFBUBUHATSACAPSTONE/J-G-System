<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminBookingsTest extends TestCase
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

        // Every route in this file sits behind ['auth', 'role:admin'].
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_bookings_dashboard_reads_persisted_records_and_json_endpoint_returns_json(): void
    {
        $booking = $this->booking();

        $this->get(route('admin.bookings'))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('Maya Santos')
            ->assertSee('data-booking-row', false)
            ->assertSee('data-search="', false)
            ->assertSee('data-booking="', false)
            ->assertSee('name="guest_count"', false)
            ->assertSee('name="venue_type"', false)
            ->assertDontSee('<<<<<<<', false)
            ->assertDontSee('>>>>>>>', false);

        $this->getJson(route('admin.bookings.data'))
            ->assertOk()
            ->assertJsonPath('bookings.0.id', $booking->id)
            ->assertJsonPath('bookings.0.client.name', 'Maya Santos')
            ->assertJsonPath('bookings.0.event.type', 'Birthday Party')
            ->assertJsonPath('bookings.0.payment.method', 'GCash');
    }

    public function test_admin_can_update_booking_status_and_event_details_through_json(): void
    {
        $booking = $this->booking();

        $this->postJson(route('admin.bookings.status', $booking), ['action' => 'approve'])
            ->assertOk()
            ->assertJsonPath('booking.status', 'pending_payment');
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'pending_payment',
        ]);

        $this->postJson(route('admin.bookings.status', $booking), ['action' => 'verify'])
            ->assertOk()
            ->assertJsonPath('booking.status', 'approved')
            ->assertJsonPath('booking.payment.state', 'partial');

        $this->patchJson(route('admin.bookings.update', $booking), [
            'client_name' => 'Maya Santos',
            'client_email' => 'maya@example.com',
            'client_phone' => '09123456789',
            'client_address' => 'Sample address',
            'event_name' => 'Updated celebration',
            'event_type' => 'Birthday Party',
            'event_location' => 'New venue',
            'venue_contact_person' => '09123456789',
            'guest_count' => '150',
            'venue_type' => 'Both',
        ])
            ->assertOk()
            ->assertJsonPath('booking.event.name', 'Updated celebration')
            ->assertJsonPath('booking.event.location', 'New venue')
            ->assertJsonPath('booking.event.guests', 150)
            ->assertJsonPath('booking.client.phone', '09123456789');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'client_phone' => '09123456789',
            'client_address' => 'Sample address',
        ]);
        $this->assertDatabaseHas('booking_events', [
            'booking_id' => $booking->id,
            'event_name' => 'Updated celebration',
            'event_contact_person' => '09123456789',
            'guest_count' => 150,
            'venue_type' => 'Both',
        ]);
    }

    public function test_admin_can_decline_a_pending_booking(): void
    {
        $booking = $this->booking();

        $this->postJson(route('admin.bookings.status', $booking), ['action' => 'decline'])
            ->assertOk()
            ->assertJsonPath('booking.status', 'declined');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'declined',
        ]);
    }

    public function test_admin_can_cancel_a_booking_waiting_for_payment(): void
    {
        $booking = $this->booking(['status' => 'pending_payment']);

        $this->postJson(route('admin.bookings.status', $booking), ['action' => 'cancel'])
            ->assertOk()
            ->assertJsonPath('booking.status', 'cancelled');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_admin_can_cancel_an_approved_booking(): void
    {
        $booking = $this->booking(['status' => 'approved']);

        $this->postJson(route('admin.bookings.status', $booking), ['action' => 'cancel'])
            ->assertOk()
            ->assertJsonPath('booking.status', 'cancelled');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_status_form_redirects_back_instead_of_rendering_json(): void
    {
        $booking = $this->booking();

        $this->from(route('admin.bookings'))
            ->withHeader('Accept', 'text/html')
            ->post(route('admin.bookings.status', $booking), ['action' => 'approve'])
            ->assertRedirect(route('admin.bookings'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'pending_payment',
        ]);
    }

    public function test_status_controller_returns_json_errors_for_json_requests(): void
    {
        $booking = $this->booking();

        $this->postJson(route('admin.bookings.status', $booking), ['action' => 'invalid'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['action']]);
    }

    public function test_bookings_page_lists_client_cancelled_bookings_with_their_own_filter_option(): void
    {
        $userCancelled = $this->booking([
            'reference' => 'JG-UC-1',
            'status' => 'user_cancelled',
            'client_name' => 'Client Cancelled Client',
        ]);
        $this->booking(['reference' => 'JG-UC-PENDING', 'client_name' => 'Pending Client']);

        // The Status filter offers "User Cancelled" (config/admin/bookings.php, no Blade edit).
        $this->get(route('admin.bookings'))
            ->assertOk()
            ->assertSee('User Cancelled')
            ->assertSee($userCancelled->reference)
            ->assertSee('Client Cancelled Client');

        $this->getJson(route('admin.bookings.data'))
            ->assertOk()
            ->assertJsonCount(2, 'bookings')
            ->assertJsonFragment(['id' => $userCancelled->id, 'status' => 'user_cancelled']);
    }

    public function test_admin_status_actions_are_not_offered_on_client_cancelled_bookings(): void
    {
        $booking = $this->booking(['status' => 'user_cancelled']);

        // No Approve/Decline/Cancel/Verify button on a booking the client already cancelled…
        $this->get(route('admin.bookings'))
            ->assertOk()
            ->assertDontSee('name="action"', false);

        // …and the server refuses those actions too.
        $this->postJson(route('admin.bookings.status', $booking), ['action' => 'cancel'])
            ->assertStatus(422);
        $this->postJson(route('admin.bookings.status', $booking), ['action' => 'approve'])
            ->assertStatus(422);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'user_cancelled',
        ]);
    }

    public function test_approval_returns_json_conflict_when_capacity_is_full(): void
    {
        $date = Carbon::today()->addDays(40)->toDateString();
        foreach (range(1, config('scheduling.max_events_per_day')) as $index) {
            $this->booking([
                'reference' => "JG-FULL-{$index}",
                'status' => 'approved',
                'event_start_date' => $date,
                'event_end_date' => $date,
            ]);
        }

        $pending = $this->booking([
            'reference' => 'JG-PENDING-FULL',
            'event_start_date' => $date,
            'event_end_date' => $date,
        ]);

        $this->postJson(route('admin.bookings.status', $pending), ['action' => 'approve'])
            ->assertStatus(409)
            ->assertJsonPath('full_dates.0', $date);

        $this->assertDatabaseHas('bookings', [
            'id' => $pending->id,
            'status' => 'pending',
        ]);
    }

    public function test_approving_a_second_booking_with_an_overlapping_schedule_is_refused(): void
    {
        $date = Carbon::today()->addDays(40)->toDateString();

        $first = $this->booking([
            'reference' => 'JG-OVERLAP-1',
            'event_start_date' => $date,
            'event_end_date' => $date,
            'event_start_time' => '6:00 PM',
            'event_end_time' => '11:00 PM',
        ]);
        $second = $this->booking([
            'reference' => 'JG-OVERLAP-2',
            'event_start_date' => $date,
            'event_end_date' => $date,
            'event_start_time' => '8:00 PM',
            'event_end_time' => '10:00 PM',
        ]);
        $third = $this->booking([
            'reference' => 'JG-NO-CLASH',
            'event_start_date' => $date,
            'event_end_date' => $date,
            'event_start_time' => '9:00 AM',
            'event_end_time' => '11:00 AM',
        ]);

        // Pending bookings hold no slot yet, so the first approval is fine.
        $this->postJson(route('admin.bookings.status', $first), ['action' => 'approve'])
            ->assertOk()
            ->assertJsonPath('booking.status', 'pending_payment');

        // The second overlaps the booking that now holds the slot: refused, status untouched.
        $this->postJson(route('admin.bookings.status', $second), ['action' => 'approve'])
            ->assertStatus(409)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'schedule conflicts with #JG-OVERLAP-1'));

        $this->assertDatabaseHas('bookings', [
            'id' => $second->id,
            'status' => 'pending',
        ]);

        // A non-overlapping booking on the same day still goes through (only 1 of 3 slots used).
        $this->postJson(route('admin.bookings.status', $third), ['action' => 'approve'])
            ->assertOk()
            ->assertJsonPath('booking.status', 'pending_payment');
    }

    public function test_verifying_a_payment_with_an_overlapping_schedule_is_refused(): void
    {
        $date = Carbon::today()->addDays(40)->toDateString();

        $first = $this->booking([
            'reference' => 'JG-VERIFY-CLASH-1',
            'status' => 'pending_payment',
            'event_start_date' => $date,
            'event_end_date' => $date,
            'event_start_time' => '6:00 PM',
            'event_end_time' => '11:00 PM',
        ]);
        $second = $this->booking([
            'reference' => 'JG-VERIFY-CLASH-2',
            'status' => 'pending_payment',
            'event_start_date' => $date,
            'event_end_date' => $date,
            'event_start_time' => '8:00 PM',
            'event_end_time' => '10:00 PM',
        ]);

        $this->postJson(route('admin.bookings.status', $first), ['action' => 'verify'])
            ->assertStatus(409)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'schedule conflicts with #JG-VERIFY-CLASH-2'));

        $this->assertDatabaseHas('bookings', [
            'id' => $first->id,
            'status' => 'pending_payment',
        ]);

        // A booking whose schedule clashes with nothing can still be verified.
        $solo = $this->booking([
            'reference' => 'JG-VERIFY-SOLO',
            'status' => 'pending_payment',
            'event_start_date' => $date,
            'event_end_date' => $date,
            'event_start_time' => '9:00 AM',
            'event_end_time' => '11:00 AM',
        ]);

        $this->postJson(route('admin.bookings.status', $solo), ['action' => 'verify'])
            ->assertOk()
            ->assertJsonPath('booking.status', 'approved');

        unset($second);
    }

    public function test_verifying_a_full_payment_marks_it_fully_paid(): void
    {
        $booking = $this->booking([
            'status' => 'pending_payment',
            'payment_method' => 'full',
            'payment_state' => 'pending',
        ]);

        $this->postJson(route('admin.bookings.status', $booking), ['action' => 'verify'])
            ->assertOk()
            ->assertJsonPath('booking.status', 'approved')
            ->assertJsonPath('booking.payment.state', 'paid')
            ->assertJsonPath('booking.payment.status', 'Fully Paid');

        $this->assertDatabaseHas('booking_payments', [
            'booking_id' => $booking->id,
            'payment_state' => 'paid',
        ]);
    }

    public function test_verifying_a_down_payment_marks_it_down_payment_paid(): void
    {
        $booking = $this->booking([
            'status' => 'pending_payment',
            'payment_method' => 'down',
            'payment_state' => 'pending',
        ]);

        $this->postJson(route('admin.bookings.status', $booking), ['action' => 'verify'])
            ->assertOk()
            ->assertJsonPath('booking.status', 'approved')
            ->assertJsonPath('booking.payment.state', 'partial')
            ->assertJsonPath('booking.payment.status', 'Down payment paid');

        $this->assertDatabaseHas('booking_payments', [
            'booking_id' => $booking->id,
            'payment_state' => 'partial',
        ]);
    }

    public function test_verifying_keeps_an_already_fully_paid_payment_paid(): void
    {
        // A booking that already recorded a full payment (re-verified) must never be downgraded.
        $booking = $this->booking([
            'status' => 'pending_payment',
            'payment_method' => 'down',
            'payment_state' => 'paid',
        ]);

        $this->postJson(route('admin.bookings.status', $booking), ['action' => 'verify'])
            ->assertOk()
            ->assertJsonPath('booking.payment.state', 'paid')
            ->assertJsonPath('booking.payment.status', 'Fully Paid');
    }

    private function booking(array $overrides = []): Booking
    {
        static $sequence = 0;
        $sequence++;
        $date = Carbon::today()->addDays(30)->toDateString();

        $values = array_merge([
            'reference' => "JG-TEST-{$sequence}",
            'status' => 'pending',
            'client_name' => 'Maya Santos',
            'client_email' => 'maya@example.com',
            'submitted_at' => now(),
        ], $overrides);

        $booking = Booking::query()->create(collect($values)->only([
            'reference',
            'status',
            'client_name',
            'client_email',
            'client_phone',
            'client_address',
            'package_id',
            'submitted_at',
        ])->all());

        $booking->eventDetails()->create([
            'event_name' => $values['event_name'] ?? 'Birthday',
            'event_type' => $values['event_type'] ?? 'Birthday Party',
            'event_location' => $values['event_location'] ?? 'Sample venue',
        ]);
        $booking->schedule()->create([
            'event_start_date' => $values['event_start_date'] ?? $date,
            'event_end_date' => $values['event_end_date'] ?? $date,
            'event_start_time' => $values['event_start_time'] ?? '6:00 PM',
            'event_end_time' => $values['event_end_time'] ?? '11:00 PM',
        ]);
        $booking->payment()->create([
            'payment_method' => $values['payment_method'] ?? 'GCash',
            'payment_state' => $values['payment_state'] ?? 'pending',
        ]);

        return $booking;
    }
}
