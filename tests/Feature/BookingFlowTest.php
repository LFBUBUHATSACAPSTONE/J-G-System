<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::connection('sqlite')->create('packages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->boolean('available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
        });
        foreach (config('packages') as $id => $package) {
            Package::create([
                'id' => $id,
                'name' => $package['name'],
                'price' => $package['price'],
                'available' => true,
                'sort_order' => count(Package::all()) + 1,
                'features' => $package['features'],
            ]);
        }

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
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->restrictOnDelete();
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
            $table->foreignId('booking_id')->unique()->constrained('bookings')->restrictOnDelete();
            $table->date('event_start_date')->index();
            $table->date('event_end_date');
            $table->string('event_start_time')->nullable();
            $table->string('event_end_time')->nullable();
            $table->timestamps();
        });
        Schema::connection('sqlite')->create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->restrictOnDelete();
            $table->string('payment_method')->nullable();
            $table->string('payment_state')->nullable()->index();
            $table->string('payment_reference')->nullable();
            $table->string('payment_receipt_path')->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function test_booking_steps_save_a_validated_booking_and_return_its_reference(): void
    {
        $this->get('/user/landing')->assertOk();
        $this->get('/user/booking?package=budget-party')
            ->assertOk()
            // The schedule step spells out the same gap the backend enforces.
            ->assertSee('2 hours');

        $this->postJson(route('booking.client-information'), [
            'first_name' => 'Taylor',
            'last_name' => 'Jones',
            'email' => 'taylor@example.com',
            'contact_number' => '09171234567',
            'address' => 'San Ildefonso, Bulacan',
        ])->assertOk()->assertExactJson(['ok' => true]);

        $this->postJson(route('booking.event-information'), [
            'event_name' => 'Birthday Party',
            'event_type' => 'Others',
            'event_type_other' => 'Anniversary',
            'event_location' => 'San Ildefonso, Bulacan',
            'venue_contact_person' => '09179876543',
            'guest_count' => 80,
            'venue_type' => 'Indoor',
        ])->assertOk();

        $date = now()->addDays(10)->toDateString();
        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date,
            'event_end_date' => $date,
            'start_time' => '6:00 PM',
            'end_time' => '10:00 PM',
        ])->assertOk();

        $this->post(route('booking.booking-summary'), [
            'payment_option' => 'down',
            'down_payment_amount' => 2900,
            'payment_proof' => UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()
            ->assertJsonValidationErrors('down_payment_amount');

        // The payment screenshot is required: posting without it is rejected before the
        // down-payment amount check even runs.
        $this->post(route('booking.booking-summary'), [
            'payment_option' => 'down',
            'down_payment_amount' => 3000,
        ], ['Accept' => 'application/json'])->assertUnprocessable()
            ->assertJsonValidationErrors('payment_proof');

        $response = $this->post(route('booking.booking-summary'), [
            'payment_option' => 'down',
            'down_payment_amount' => 3000,
            'payment_proof' => UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['reference']);

        $booking = Booking::where('reference', $response->json('reference'))->firstOrFail();
        $this->assertDatabaseHas('bookings', [
            'reference' => $response->json('reference'),
            'status' => 'pending',
            'client_name' => 'Taylor Jones',
            'package_id' => 'budget-party',
        ]);
        $this->assertDatabaseHas('booking_events', [
            'booking_id' => $booking->id,
            'event_type' => 'Others',
            'event_type_other' => 'Anniversary',
        ]);
        $this->assertDatabaseHas('booking_payments', [
            'booking_id' => $booking->id,
            'payment_method' => 'down',
            'payment_amount' => 3000,
        ]);
        // The uploaded proof is stored and exposed to the admin modal as payment.proof_url.
        $this->assertNotNull($booking->payment->payment_receipt_path);
        Storage::disk('public')->assertExists($booking->payment->payment_receipt_path);
        $this->assertStringContainsString(
            $booking->payment->payment_receipt_path,
            (string) $booking->toAdminArray()['payment']['proof_url'],
        );
        $this->assertSame('Anniversary', $booking->eventDetails->event_type_other);
        $this->assertSame($date, $booking->schedule->event_start_date->toDateString());
        $this->assertSame('3000.00', $booking->payment->payment_amount);
        $this->assertSame('budget-party', $booking->package->id);
        $this->assertSame('Budget Party', $booking->package->name);
    }

    public function test_availability_reports_dates_at_capacity_as_json(): void
    {
        $date = now()->addDays(14)->toDateString();
        foreach (range(1, config('scheduling.max_events_per_day')) as $number) {
            $this->createBooking($this->bookingData($date, "CAP{$number}"));
        }

        $this->getJson(route('booking.availability', ['month' => substr($date, 0, 7)]))
            ->assertOk()
            ->assertJsonPath('month', substr($date, 0, 7))
            ->assertJsonFragment(['full' => [$date]]);
    }

    public function test_bookings_on_the_same_day_cannot_overlap_or_skip_the_gap(): void
    {
        $date = now()->addDays(21)->toDateString();

        // An approved event from 11:00 AM to 6:00 PM holds 9:00 AM - 8:00 PM once the
        // default 120-minute break is added on both sides.
        $this->createBooking(array_merge($this->bookingData($date, 'BUSY1'), [
            'event_start_time' => '11:00 AM',
            'event_end_time' => '6:00 PM',
        ]));

        $this->reachScheduleStep();

        // Direct overlap: rejected, and the message names the blocked window.
        $overlap = $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date,
            'event_end_date' => $date,
            'start_time' => '1:00 PM',
            'end_time' => '3:00 PM',
        ])->assertUnprocessable()->assertJsonValidationErrors('start_time');
        $this->assertStringContainsString('unavailable', $overlap->json('errors.start_time.0'));

        // Too soon after the existing event: 7:00 PM is still inside the required break.
        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date,
            'event_end_date' => $date,
            'start_time' => '7:00 PM',
            'end_time' => '9:00 PM',
        ])->assertUnprocessable()->assertJsonValidationErrors('start_time');

        // Exactly one gap after 6:00 PM (8:00 PM) is allowed.
        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date,
            'event_end_date' => $date,
            'start_time' => '8:00 PM',
            'end_time' => '10:00 PM',
        ])->assertOk();
    }

    public function test_a_day_fully_covered_by_bookings_is_reported_full(): void
    {
        $date = now()->addDays(28)->toDateString();

        // 12 AM - 12 PM and 2 PM - 11:59 PM, plus the break each, leave no free window.
        $this->createBooking(array_merge($this->bookingData($date, 'FILL1'), [
            'event_start_time' => '12:00 AM',
            'event_end_time' => '12:00 PM',
        ]));
        $this->createBooking(array_merge($this->bookingData($date, 'FILL2'), [
            'event_start_time' => '2:00 PM',
            'event_end_time' => '11:59 PM',
        ]));

        // Under the daily limit (2 of 3) but the whole clock is occupied.
        $this->getJson(route('booking.availability', ['month' => substr($date, 0, 7)]))
            ->assertOk()
            ->assertJsonFragment(['full' => [$date]]);

        $this->reachScheduleStep();
        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date,
            'event_end_date' => $date,
            'start_time' => '1:00 PM',
            'end_time' => '2:00 PM',
        ])->assertUnprocessable()
            ->assertJsonPath('full_dates.0', $date);
    }

    public function test_a_fourth_booking_on_the_same_day_is_rejected(): void
    {
        $date = now()->addDays(35)->toDateString();
        foreach (range(1, config('scheduling.max_events_per_day')) as $number) {
            $this->createBooking($this->bookingData($date, "LIMIT{$number}"));
        }

        $this->reachScheduleStep();
        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date,
            'event_end_date' => $date,
            'start_time' => '8:00 AM',
            'end_time' => '10:00 AM',
        ])->assertUnprocessable()
            ->assertJsonPath('full_dates.0', $date);
    }

    public function test_confirming_a_booking_rechecks_the_time_slot(): void
    {
        $date = now()->addDays(42)->toDateString();
        $this->reachScheduleStep();

        // Passes the schedule step...
        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date,
            'event_end_date' => $date,
            'start_time' => '6:00 PM',
            'end_time' => '10:00 PM',
        ])->assertOk();

        // ...but by confirmation time another approved event (12 PM - 6 PM, holding the
        // slot until 8 PM with the break) has taken it.
        $this->createBooking(array_merge($this->bookingData($date, 'LATE1'), [
            'event_start_time' => '12:00 PM',
            'event_end_time' => '6:00 PM',
        ]));

        $this->post(route('booking.booking-summary'), [
            'payment_option' => 'full',
            'payment_proof' => UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_time');
    }

    public function test_a_multi_day_event_does_not_clash_with_a_day_it_only_passes_over(): void
    {
        $date = now()->addDays(56)->toDateString();
        $nextDay = now()->addDays(57)->toDateString();

        // An approved event that finishes before the new one begins, on the first day only.
        $this->createBooking(array_merge($this->bookingData($date, 'EARLY1'), [
            'event_start_time' => '1:00 AM',
            'event_end_time' => '2:00 AM',
        ]));

        $this->reachScheduleStep();

        // 6:00 PM on the first day through 2:00 AM the next morning. The 1:00 AM slot is
        // hours before the start, so neither day clashes: accepted.
        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date,
            'event_end_date' => $nextDay,
            'start_time' => '6:00 PM',
            'end_time' => '2:00 AM',
        ])->assertOk();
    }

    public function test_an_event_running_past_midnight_clashes_with_a_late_event_on_its_first_day(): void
    {
        $date = now()->addDays(63)->toDateString();
        $nextDay = now()->addDays(64)->toDateString();

        // 10:00 PM - 10:30 PM holds 8:00 PM - 12:00 AM once the 120-minute break is added.
        $this->createBooking(array_merge($this->bookingData($date, 'LATE1'), [
            'event_start_time' => '10:00 PM',
            'event_end_time' => '10:30 PM',
        ]));

        $this->reachScheduleStep();

        // 7:00 PM - 2:00 AM runs straight through 10:00 PM on its first day.
        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date,
            'event_end_date' => $nextDay,
            'start_time' => '7:00 PM',
            'end_time' => '2:00 AM',
        ])->assertUnprocessable()->assertJsonValidationErrors('start_time');
    }

    public function test_a_multi_day_event_fills_its_middle_days(): void
    {
        $first = now()->addDays(63)->toDateString();
        $middle = now()->addDays(64)->toDateString();
        $last = now()->addDays(65)->toDateString();

        // A short event in the small hours of the event's middle day: the multi-day event
        // runs through that whole day, so the slot is taken.
        $this->createBooking(array_merge($this->bookingData($middle, 'MIDDLE1'), [
            'event_start_time' => '1:00 AM',
            'event_end_time' => '2:00 AM',
        ]));

        $this->reachScheduleStep();

        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $first,
            'event_end_date' => $last,
            'start_time' => '9:00 AM',
            'end_time' => '5:00 PM',
        ])->assertUnprocessable()->assertJsonValidationErrors('start_time');
    }

    public function test_availability_marks_the_middle_days_of_a_multi_day_booking_full(): void
    {
        $first = now()->addDays(70)->toDateString();
        $middle = now()->addDays(71)->toDateString();
        $last = now()->addDays(72)->toDateString();

        $this->createBooking(array_merge($this->bookingData($first, 'SPAN1'), [
            'event_end_date' => $last,
            'event_start_time' => '9:00 AM',
            'event_end_time' => '5:00 PM',
        ]));

        // Only the middle day is held from end to end; the first and last days still have
        // free time before the start / after the end.
        $this->getJson(route('booking.availability', ['month' => substr($middle, 0, 7)]))
            ->assertOk()
            ->assertJsonFragment(['full' => [$middle]]);
    }

    public function test_an_event_shorter_than_one_hour_is_rejected(): void
    {
        $date = now()->addDays(77)->toDateString();
        $this->reachScheduleStep();

        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date,
            'event_end_date' => $date,
            'start_time' => '6:00 PM',
            'end_time' => '6:30 PM',
        ])->assertUnprocessable()->assertJsonValidationErrors('end_time');

        // Exactly one hour is the shortest the browser allows, so the server accepts it too.
        $this->postJson(route('booking.event-schedule'), [
            'event_start_date' => $date,
            'event_end_date' => $date,
            'start_time' => '6:00 PM',
            'end_time' => '7:00 PM',
        ])->assertOk();
    }

    public function test_authenticated_package_cards_link_to_the_selected_package(): void
    {
        $this->actingAs(new User(['name' => 'Test User', 'email' => 'test@example.com']))
            ->get('/user/landing')
            ->assertOk()
            ->assertSee('/user/booking?package=budget-lite', false)
            ->assertSee('/user/booking?package=elite-symphony', false);
    }

    public function test_cancelled_booking_can_be_rescheduled_once_without_reentering_payment(): void
    {
        $originalDate = now()->addMonth()->startOfMonth()->addDays(10)->toDateString();
        $original = $this->createBooking(array_merge($this->bookingData($originalDate, 'CANCEL1'), [
            'status' => 'user_cancelled',
            'client_phone' => '09171234567',
            'client_address' => 'San Ildefonso, Bulacan',
            'package_id' => 'budget-party',
            'package_price' => 10000,
            'payment_method' => 'down',
            'payment_state' => 'pending',
            'payment_amount' => 3000,
        ]));

        $this->get('/user/booking?reschedule='.$original->id)->assertOk();

        $newDate = now()->addMonth()->startOfMonth()->addDays(11)->toDateString();
        $this->postJson(route('booking.event-schedule'), [
            'reschedule_id' => (string) $original->id,
            'event_start_date' => $newDate,
            'event_end_date' => $newDate,
            'start_time' => '6:00 PM',
            'end_time' => '10:00 PM',
        ])->assertOk();

        $response = $this->postJson(route('booking.booking-summary'), [
            'reschedule_id' => (string) $original->id,
        ])->assertOk()->assertJsonStructure(['reference']);

        $rescheduled = Booking::where('reference', $response->json('reference'))->firstOrFail();
        $this->assertSame($original->id, $rescheduled->rescheduled_from_id);
        $this->assertSame($newDate, $rescheduled->schedule->event_start_date->toDateString());
        $this->assertSame('down', $rescheduled->payment->payment_method);
        $this->assertSame('3000.00', $rescheduled->payment->payment_amount);
        $this->assertSame('Wedding', $rescheduled->eventDetails->event_type);
        $this->assertSame('down', $rescheduled->payment->payment_method);
        $this->assertSame('budget-party', $rescheduled->package->id);

        $this->get('/user/booking?reschedule='.$original->id)->assertForbidden();
    }

    private function createBooking(array $data): Booking
    {
        $booking = Booking::create(array_merge([
            'status' => 'approved',
            'client_phone' => '09171234567',
            'client_address' => 'Test Address',
            'submitted_at' => now(),
        ], array_intersect_key($data, array_flip([
            'user_id',
            'reference',
            'status',
            'client_name',
            'client_email',
            'client_phone',
            'client_address',
            'package_id',
            'rescheduled_from_id',
            'submitted_at',
        ]))));
        $booking->eventDetails()->create([
            'event_name' => $data['event_name'] ?? 'Test Event',
            'event_type' => $data['event_type'] ?? 'Wedding',
            'event_type_other' => $data['event_type_other'] ?? null,
            'event_location' => $data['event_location'] ?? 'Test Location',
            'event_contact_person' => $data['event_contact_person'] ?? null,
            'guest_count' => $data['guest_count'] ?? null,
            'venue_type' => $data['venue_type'] ?? 'Indoor',
        ]);
        $booking->schedule()->create([
            'event_start_date' => $data['event_start_date'],
            'event_end_date' => $data['event_end_date'] ?? $data['event_start_date'],
            'event_start_time' => $data['event_start_time'] ?? '6:00 PM',
            'event_end_time' => $data['event_end_time'] ?? '10:00 PM',
        ]);
        $booking->payment()->create([
            'payment_method' => $data['payment_method'] ?? null,
            'payment_state' => $data['payment_state'] ?? null,
            'payment_amount' => $data['payment_amount'] ?? null,
        ]);

        return $booking;
    }

    // Puts a complete client + event draft in the session so the Event Schedule step can
    // post straight away, exactly like walking the wizard in the browser.
    private function reachScheduleStep(): void
    {
        $this->get('/user/booking?package=budget-party')->assertOk();

        $this->postJson(route('booking.client-information'), [
            'first_name' => 'Taylor',
            'last_name' => 'Jones',
            'email' => 'taylor@example.com',
            'contact_number' => '09171234567',
            'address' => 'San Ildefonso, Bulacan',
        ])->assertOk();

        $this->postJson(route('booking.event-information'), [
            'event_name' => 'Birthday Party',
            'event_type' => 'Others',
            'event_type_other' => 'Anniversary',
            'event_location' => 'San Ildefonso, Bulacan',
            'venue_type' => 'Indoor',
        ])->assertOk();
    }

    private function bookingData(string $date, string $reference): array
    {
        return [
            'reference' => $reference,
            'status' => 'approved',
            'client_name' => 'Test User',
            'client_email' => 'test@example.com',
            'event_name' => 'Test Event',
            'event_type' => 'Wedding',
            'event_location' => 'Test Location',
            'event_start_date' => $date,
            'event_end_date' => $date,
            'package_name' => 'Budget Party',
            'package_id' => 'budget-party',
            'package_price' => 10000,
        ];
    }
}
