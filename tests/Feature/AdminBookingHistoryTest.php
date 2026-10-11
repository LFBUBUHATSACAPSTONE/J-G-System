<?php

namespace Tests\Feature;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminBookingHistoryTest extends TestCase
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

    public function test_history_lists_cancelled_and_verified_bookings_only(): void
    {
        $verified = $this->booking([
            'reference' => 'JG-HIST-VERIFIED',
            'status' => 'approved',
            'client_name' => 'Verified Client',
            'payment_state' => 'partial',
            'event_start_date' => Carbon::today()->addDays(10)->toDateString(),
            'event_end_date' => Carbon::today()->addDays(10)->toDateString(),
        ]);

        $cancelled = $this->booking([
            'reference' => 'JG-HIST-CANCELLED',
            'status' => 'cancelled',
            'client_name' => 'Cancelled Client',
            'event_start_date' => Carbon::today()->addDays(30)->toDateString(),
            'event_end_date' => Carbon::today()->addDays(30)->toDateString(),
        ]);

        $pending = $this->booking([
            'reference' => 'JG-HIST-PENDING',
            'status' => 'pending',
            'client_name' => 'Pending Client',
        ]);

        $this->get(route('admin.booking-history'))
            ->assertOk()
            ->assertSee('Verified Client')
            ->assertSee('Cancelled Client')
            ->assertDontSee('JG-HIST-PENDING');

        $this->getJson(route('admin.booking-history.data'))
            ->assertOk()
            ->assertJsonCount(2, 'bookings')
            ->assertJsonPath('bookings.0.id', $cancelled->id)
            ->assertJsonPath('bookings.0.client.name', 'Cancelled Client')
            ->assertJsonPath('bookings.1.id', $verified->id)
            ->assertJsonPath('bookings.1.payment.state', 'partial')
            ->assertJsonMissing(['id' => $pending->id]);
    }

    public function test_history_reads_event_payment_and_package_from_relations(): void
    {
        $this->booking([
            'reference' => 'JG-HIST-RELATIONS',
            'status' => 'approved',
            'client_name' => 'Relation Client',
            'payment_state' => 'paid',
            'package_id' => 'budget-lite',
            'event_name' => 'Grand Reunion',
            'event_type' => 'Family Reunion',
            'event_location' => 'Grand Hall',
            'event_start_date' => Carbon::today()->addDays(5)->toDateString(),
            'event_end_date' => Carbon::today()->addDays(6)->toDateString(),
        ]);

        $this->getJson(route('admin.booking-history.data'))
            ->assertOk()
            ->assertJsonCount(1, 'bookings')
            ->assertJsonPath('bookings.0.event.name', 'Grand Reunion')
            ->assertJsonPath('bookings.0.event.type', 'Family Reunion')
            ->assertJsonPath('bookings.0.event.location', 'Grand Hall')
            ->assertJsonPath('bookings.0.payment.state', 'paid')
            ->assertJsonPath('bookings.0.package.id', 'budget-lite')
            ->assertJsonPath('bookings.0.package.name', 'Budget Lite')
            ->assertJsonPath('packages.0.id', 'budget-lite')
            ->assertJsonPath('packages.0.name', 'Budget Lite');
    }

    private function booking(array $overrides = []): Booking
    {
        static $sequence = 0;
        $sequence++;
        $date = Carbon::today()->addDays(30)->toDateString();

        $values = array_merge([
            'reference' => "JG-HIST-{$sequence}",
            'status' => 'pending',
            'client_name' => 'History Client',
            'client_email' => 'client@example.com',
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