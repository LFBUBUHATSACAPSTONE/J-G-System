<?php

namespace Tests\Feature;

use App\Models\Booking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

        Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_10_06_120000_create_bookings_table.php',
            '--force' => true,
        ]);

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

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSee('data-admin-dashboard', false);

        $response = $this->getJson(route('admin.dashboard.data'))->assertOk();
        $this->assertSame([
            'total' => ['value' => 4, 'change' => 2],
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
            'budget-lite' => 1,
            'budget-party' => 1,
            'luxe-lite' => 1,
            'modern-glam' => 1,
        ], $counts);
    }

    private function createBooking(array $overrides = []): Booking
    {
        $booking = Booking::create(array_merge([
            'reference' => 'JG00000',
            'status' => 'pending',
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
            'event_name' => 'Test Event',
            'event_type' => 'Birthday Party',
            'event_location' => 'Test Venue',
            'event_start_date' => '2026-12-01',
            'event_end_date' => '2026-12-01',
            'package_id' => 'test-package',
            'package_name' => 'Test Package',
            'submitted_at' => '2026-10-01 09:00:00',
        ], $overrides));

        if (isset($overrides['created_at'])) {
            $booking->forceFill([
                'created_at' => $overrides['created_at'],
                'updated_at' => $overrides['updated_at'] ?? $overrides['created_at'],
            ])->save();
        }

        return $booking;
    }
}
