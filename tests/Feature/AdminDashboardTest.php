<?php

namespace Tests\Feature;

use App\Models\Booking;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('packages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->boolean('available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
        });
        foreach (['budget-party', 'luxe-lite', 'modern-glam', 'budget-lite', 'test-package'] as $packageId) {
            DB::table('packages')->insert([
                'id' => $packageId,
                'name' => $packageId,
                'price' => 10000,
                'available' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([
            '2026_10_06_120000_create_bookings_table.php',
            '2026_10_10_090000_add_payment_and_reschedule_data_to_bookings_table.php',
            '2026_10_10_100000_create_booking_detail_tables.php',
            '2026_10_10_110000_create_booking_packages_table.php',
            '2026_10_10_120000_remove_normalized_booking_columns.php',
            '2026_10_10_130000_link_bookings_to_packages.php',
        ] as $migration) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/'.$migration,
                '--force' => true,
            ]);
        }

        Carbon::setTestNow('2026-10-06 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_dashboard_json_contains_database_backed_stats_queues_and_package_totals(): void
    {
        $this->createBooking([
            'reference' => 'JG12345',
            'status' => 'pending',
            'client_name' => 'Arjay Dela Cruz',
            'event_name' => 'Sample Birthday',
            'event_start_date' => '2026-12-30',
            'package_id' => 'budget-party',
            'package_name' => 'Budget Party',
            'submitted_at' => '2026-10-02 09:00:00',
        ]);
        $this->createBooking([
            'reference' => 'JG63497',
            'status' => 'pending_payment',
            'client_name' => 'Lhester Pile',
            'event_name' => 'Pile Family Reunion',
            'event_start_date' => '2026-12-31',
            'package_id' => 'luxe-lite',
            'package_name' => 'Luxe Lite',
            'submitted_at' => '2026-10-04 09:00:00',
        ]);
        $this->createBooking([
            'reference' => 'JG39201',
            'status' => 'approved',
            'payment_state' => 'paid',
            'client_name' => 'Ayessa Dumay',
            'event_name' => "Aye's Concert",
            'event_start_date' => '2026-10-22',
            'event_start_time' => '17:00',
            'event_end_time' => '23:00',
            'package_id' => 'modern-glam',
            'package_name' => 'Modern Glam',
        ]);
        $this->createBooking([
            'reference' => 'JG11085',
            'status' => 'cancelled',
            'package_id' => 'budget-lite',
            'package_name' => 'Budget Lite',
            'created_at' => '2026-09-20 09:00:00',
            'updated_at' => '2026-09-20 09:00:00',
        ]);
        $this->createBooking([
            'reference' => 'JG20266',
            'status' => 'completed',
            'package_id' => 'modern-glam',
            'package_name' => 'Modern Glam',
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSee('data-admin-dashboard', false)
            ->assertSee('data-dashboard-stats', false)
            ->assertSee('data-dashboard-attention', false)
            ->assertSee('data-dashboard-upcoming', false)
            ->assertSee('data-dashboard-package-rate', false)
            ->assertSee('(approved)', false);

        $response = $this->getJson(route('admin.dashboard.data'))->assertOk();
        $this->assertSame([
            'total' => ['value' => 5, 'change' => 3],
            'confirmed' => ['value' => 1, 'change' => 1],
            'pending' => ['value' => 1, 'change' => 1],
            'payment' => ['value' => 1, 'change' => 1],
            'cancelled' => ['value' => 1, 'change' => -1],
        ], $response->json('stats'));
        $this->assertSame(
            ['#JG12345', '#JG63497'],
            array_column($response->json('attention'), 'reference'),
        );
        $this->assertSame(["Aye's Concert"], array_column($response->json('upcomingEvents'), 'event'));
        $this->assertSame(
            '5:00 PM - 11:00 PM',
            $response->json('upcomingEvents.0.time'),
        );

        $counts = array_column($response->json('packageRate'), 'count', 'id');
        ksort($counts);
        $this->assertSame([
            'modern-glam' => 2,
        ], $counts);
    }

    public function test_dashboard_returns_incomplete_attention_booking_without_failing(): void
    {
        $booking = Booking::query()->create([
            'reference' => 'JG-MISSING-DETAILS',
            'status' => 'pending',
            'client_name' => 'Incomplete Client',
            'client_email' => 'incomplete@example.com',
        ]);

        $response = $this->getJson(route('admin.dashboard.data'))->assertOk();

        $this->assertSame(
            $booking->id,
            $response->json('attention.0.id'),
        );
        $this->assertNull($response->json('attention.0.date'));
        $this->assertNull($response->json('attention.0.package'));
    }

    private function createBooking(array $overrides = []): Booking
    {
        $data = array_merge([
            'reference' => 'JG00000',
            'status' => 'pending',
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
            'event_name' => 'Test Event',
            'event_type' => 'Birthday Party',
            'event_location' => 'Test Venue',
            'event_start_date' => '2026-12-01',
            'event_end_date' => '2026-12-01',
            'event_start_time' => '17:00',
            'event_end_time' => '23:00',
            'package_id' => 'test-package',
            'package_name' => 'Test Package',
            'submitted_at' => '2026-10-01 09:00:00',
        ], $overrides);
        $booking = Booking::create(array_intersect_key($data, array_flip([
            'reference',
            'status',
            'client_name',
            'client_email',
            'client_phone',
            'client_address',
            'package_id',
            'submitted_at',
        ])));

        if (isset($overrides['created_at'])) {
            $booking->forceFill([
                'created_at' => $overrides['created_at'],
                'updated_at' => $overrides['updated_at'] ?? $overrides['created_at'],
            ])->save();
        }

        $booking->eventDetails()->create([
            'event_name' => $data['event_name'],
            'event_type' => $data['event_type'] ?? 'Birthday Party',
            'event_type_other' => $data['event_type_other'] ?? null,
            'event_location' => $data['event_location'] ?? 'Test Venue',
            'event_contact_person' => $data['event_contact_person'] ?? null,
            'guest_count' => $data['guest_count'] ?? null,
            'venue_type' => $data['venue_type'] ?? null,
        ]);
        $booking->schedule()->create([
            'event_start_date' => $data['event_start_date'],
            'event_end_date' => $data['event_end_date'],
            'event_start_time' => $data['event_start_time'],
            'event_end_time' => $data['event_end_time'],
        ]);
        $booking->payment()->create([
            'payment_method' => $data['payment_method'] ?? null,
            'payment_state' => $data['payment_state'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'payment_receipt_path' => $data['payment_receipt_path'] ?? null,
            'payment_amount' => $data['payment_amount'] ?? null,
        ]);

        return $booking;
    }
}
