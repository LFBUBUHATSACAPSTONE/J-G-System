<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        Schema::connection('sqlite')->create('booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->restrictOnDelete();
            $table->string('package_id')->nullable();
            $table->string('package_name');
            $table->decimal('package_price', 10, 2)->nullable();
            $table->timestamps();
            $table->foreign('package_id')->references('id')->on('packages')->restrictOnDelete();
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
        $this->get('/user/booking?package=budget-party')->assertOk();

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

        $this->postJson(route('booking.booking-summary'), [
            'payment_option' => 'down',
            'down_payment_amount' => 2900,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('down_payment_amount');

        $response = $this->postJson(route('booking.booking-summary'), [
            'payment_option' => 'down',
            'down_payment_amount' => 3000,
        ])->assertOk()
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
