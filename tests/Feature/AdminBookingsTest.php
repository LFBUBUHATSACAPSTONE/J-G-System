<?php

namespace Tests\Feature;

use App\Models\Booking;
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

        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_10_07_060000_create_bookings_table.php'),
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    public function test_bookings_dashboard_reads_persisted_records_and_json_endpoint_returns_json(): void
    {
        $booking = $this->booking();

        $this->get(route('admin.bookings'))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('Maya Santos');

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
            'event_name' => 'Updated celebration',
            'event_location' => 'New venue',
        ])
            ->assertOk()
            ->assertJsonPath('booking.event.name', 'Updated celebration')
            ->assertJsonPath('booking.event.location', 'New venue');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'event_name' => 'Updated celebration',
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

    public function test_status_controller_returns_json_for_invalid_non_json_requests(): void
    {
        $booking = $this->booking();

        $this->post(route('admin.bookings.status', $booking), ['action' => 'invalid'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['action']]);
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

    private function booking(array $overrides = []): Booking
    {
        static $sequence = 0;
        $sequence++;
        $date = Carbon::today()->addDays(30)->toDateString();

        return Booking::query()->create(array_merge([
            'reference' => "JG-TEST-{$sequence}",
            'status' => 'pending',
            'client_name' => 'Maya Santos',
            'client_email' => 'maya@example.com',
            'event_name' => 'Birthday',
            'event_type' => 'Birthday Party',
            'event_location' => 'Sample venue',
            'event_start_date' => $date,
            'event_end_date' => $date,
            'event_start_time' => '6:00 PM',
            'event_end_time' => '11:00 PM',
            'payment_method' => 'GCash',
            'payment_state' => 'pending',
            'package_id' => 'budget-party',
            'package_name' => 'Budget Party',
            'package_price' => 12000,
            'submitted_at' => now(),
        ], $overrides));
    }
}
