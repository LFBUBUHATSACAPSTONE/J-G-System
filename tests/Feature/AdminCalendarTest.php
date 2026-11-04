<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCalendarTest extends TestCase
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

    public function test_calendar_page_and_json_show_only_approved_bookings(): void
    {
        $this->booking([
            'reference' => 'JG-CAL-APPROVED',
            'status' => 'approved',
            'client_name' => 'Approved Client',
            'package_id' => 'budget-lite',
            'event_start_date' => Carbon::today()->addDay()->toDateString(),
            'event_end_date' => Carbon::today()->addDay()->toDateString(),
        ]);

        $this->booking([
            'reference' => 'JG-CAL-PENDING',
            'status' => 'pending',
            'client_name' => 'Pending Client',
            'event_start_date' => Carbon::today()->addDays(2)->toDateString(),
            'event_end_date' => Carbon::today()->addDays(2)->toDateString(),
        ]);

        $this->booking([
            'reference' => 'JG-CAL-CANCELLED',
            'status' => 'cancelled',
            'client_name' => 'Cancelled Client',
            'event_start_date' => Carbon::today()->addDays(3)->toDateString(),
            'event_end_date' => Carbon::today()->addDays(3)->toDateString(),
        ]);

        // Only approved bookings are on the calendar (config/admin/calendar.php 'statuses'):
        // anything still waiting for a decision or already cancelled keeps its date free here.
        $this->get(route('admin.calendar'))
            ->assertOk()
            ->assertViewIs('admin.calendar')
            ->assertSee('JG-CAL-APPROVED', false)
            ->assertDontSee('JG-CAL-PENDING', false)
            ->assertDontSee('JG-CAL-CANCELLED', false);

        $this->getJson(route('admin.calendar.data'))
            ->assertOk()
            ->assertJsonCount(1, 'events')
            ->assertJsonPath('events.0.reference', '#JG-CAL-APPROVED')
            ->assertJsonPath('events.0.status', 'approved')
            ->assertJsonMissing(['reference' => '#JG-CAL-PENDING'])
            ->assertJsonMissing(['reference' => '#JG-CAL-CANCELLED']);
    }

    public function test_calendar_json_honours_the_requested_month_and_falls_back_when_invalid(): void
    {
        $target = Carbon::today()->addMonthNoOverflow()->startOfMonth()->addDays(4);
        $booking = $this->booking([
            'reference' => 'JG-CAL-NEXT-MONTH',
            'status' => 'approved',
            'event_start_date' => $target->toDateString(),
            'event_end_date' => $target->toDateString(),
        ]);

        $month = $target->format('Y-m');

        $response = $this->getJson(route('admin.calendar.data', ['month' => $month]))
            ->assertOk()
            ->assertJsonCount(1, 'events')
            ->assertJsonPath('events.0.id', $booking->id);

        $this->assertStringStartsWith($month, (string) $response->json('month'));

        // An invalid month falls back to the current month, which holds no booking above.
        $this->getJson(route('admin.calendar.data', ['month' => 'not-a-month']))
            ->assertOk()
            ->assertJsonCount(0, 'events');
    }

    public function test_calendar_page_arrows_link_to_the_neighbouring_months(): void
    {
        $this->get(route('admin.calendar', ['month' => '2026-05']))
            ->assertOk()
            ->assertSee(route('admin.calendar', ['month' => '2026-04']), false)
            ->assertSee(route('admin.calendar', ['month' => '2026-06']), false);
    }

    public function test_calendar_keeps_a_booking_with_a_missing_schedule_off_the_page(): void
    {
        // Incomplete legacy row: no schedule, so no dates to place it on the grid.
        Booking::query()->create([
            'reference' => 'JG-CAL-NO-SCHEDULE',
            'status' => 'approved',
            'client_name' => 'No Schedule Client',
            'client_email' => 'noschedule@example.com',
        ]);

        $this->get(route('admin.calendar'))->assertOk();
        $this->getJson(route('admin.calendar.data'))
            ->assertOk()
            ->assertJsonCount(0, 'events');
    }

    private function booking(array $overrides = []): Booking
    {
        static $sequence = 0;
        $sequence++;
        $date = Carbon::today()->addDays(30)->toDateString();

        $values = array_merge([
            'reference' => "JG-CAL-{$sequence}",
            'status' => 'pending',
            'client_name' => 'Calendar Client',
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
            'event_name' => $values['event_name'] ?? 'Calendar Event',
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
