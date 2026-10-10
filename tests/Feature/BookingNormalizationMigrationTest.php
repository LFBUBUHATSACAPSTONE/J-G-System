<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingNormalizationMigrationTest extends TestCase
{
    public function test_duplicate_booking_values_are_preserved_in_detail_tables_before_columns_are_removed(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('packages', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->boolean('available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
        });
        DB::table('packages')->insert([
            'id' => 'budget-party',
            'name' => 'Budget Party',
            'price' => 10000,
            'available' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            '2026_10_06_120000_create_bookings_table.php',
            '2026_10_10_090000_add_payment_and_reschedule_data_to_bookings_table.php',
            '2026_10_10_100000_create_booking_detail_tables.php',
            '2026_10_10_110000_create_booking_packages_table.php',
        ] as $migration) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/'.$migration,
                '--force' => true,
            ]);
        }

        $bookingId = DB::table('bookings')->insertGetId([
            'reference' => 'JG12345',
            'status' => 'pending',
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
            'event_name' => 'Test Birthday',
            'event_type' => 'Birthday Party',
            'event_type_other' => null,
            'event_location' => 'Test Venue',
            'event_contact_person' => '09171234567',
            'guest_count' => 80,
            'venue_type' => 'Indoor',
            'event_start_date' => '2026-12-01',
            'event_end_date' => '2026-12-02',
            'event_start_time' => '6:00 PM',
            'event_end_time' => '10:00 PM',
            'package_id' => 'budget-party',
            'package_name' => 'Budget Party',
            'package_price' => 10000,
            'payment_method' => 'down',
            'payment_state' => 'pending',
            'payment_reference' => 'PAY123',
            'payment_receipt_path' => 'receipts/pay123.jpg',
            'payment_amount' => 3000,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_10_10_120000_remove_normalized_booking_columns.php',
            '--force' => true,
        ]);

        $this->assertSame(1, DB::table('bookings')->count());
        $this->assertDatabaseHas('booking_events', [
            'booking_id' => $bookingId,
            'event_name' => 'Test Birthday',
            'guest_count' => 80,
        ]);
        $this->assertDatabaseHas('booking_schedules', [
            'booking_id' => $bookingId,
            'event_start_date' => '2026-12-01',
            'event_end_date' => '2026-12-02',
        ]);
        $this->assertDatabaseHas('booking_payments', [
            'booking_id' => $bookingId,
            'payment_reference' => 'PAY123',
            'payment_amount' => 3000,
        ]);
        $this->assertDatabaseHas('booking_packages', [
            'booking_id' => $bookingId,
            'package_id' => 'budget-party',
            'package_name' => 'Budget Party',
            'package_price' => 10000,
        ]);
        Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_10_10_130000_link_bookings_to_packages.php',
            '--force' => true,
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'package_id' => 'budget-party',
        ]);
        $this->assertSame(1, DB::table('booking_packages')->count());
        $this->assertTrue(Schema::hasTable('packages'));
        $this->assertTrue(Schema::hasTable('booking_events'));
        $this->assertFalse(Schema::hasColumn('bookings', 'event_name'));
        $this->assertFalse(Schema::hasColumn('bookings', 'event_start_date'));
        $this->assertFalse(Schema::hasColumn('bookings', 'payment_amount'));
        $this->assertFalse(Schema::hasColumn('bookings', 'package_name'));
    }
}
