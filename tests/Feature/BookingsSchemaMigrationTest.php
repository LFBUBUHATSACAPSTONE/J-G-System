<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingsSchemaMigrationTest extends TestCase
{
    private const EXPECTED_COLUMNS = [
        'id',
        'reference',
        'status',
        'client_name',
        'client_email',
        'event_name',
        'event_type',
        'event_location',
        'event_start_date',
        'event_end_date',
        'event_start_time',
        'event_end_time',
        'package_id',
        'package_name',
        'package_price',
        'payment_method',
        'payment_state',
        'submitted_at',
        'created_at',
        'updated_at',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
    }

    public function test_alignment_migration_keeps_only_the_requested_booking_columns_and_backfills_submission_time(): void
    {
        Schema::create('bookings', function ($table): void {
            $table->id();
            $table->string('reference');
            $table->string('status');
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone')->nullable();
            $table->string('client_address')->nullable();
            $table->string('event_name')->nullable();
            $table->string('event_type')->nullable();
            $table->string('event_location')->nullable();
            $table->string('venue_contact_person')->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->string('venue_type')->nullable();
            $table->date('event_start_date');
            $table->date('event_end_date')->nullable();
            $table->string('event_start_time')->nullable();
            $table->string('event_end_time')->nullable();
            $table->string('package_id')->nullable();
            $table->string('package_name');
            $table->decimal('package_price', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_state')->nullable();
            $table->string('payment_label')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });
        DB::table('bookings')->insert([
            'reference' => 'JG-OLD-1',
            'status' => 'pending',
            'client_name' => 'Existing Client',
            'client_email' => 'client@example.com',
            'event_start_date' => '2026-12-01',
            'package_name' => 'Package',
            'created_at' => '2026-10-01 09:30:00',
            'updated_at' => '2026-10-01 09:30:00',
        ]);

        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_10_07_080000_align_bookings_table_columns.php'),
            '--realpath' => true,
            '--force' => true,
        ]);

        $this->assertEqualsCanonicalizing(self::EXPECTED_COLUMNS, Schema::getColumnListing('bookings'));
        $this->assertDatabaseHas('bookings', [
            'reference' => 'JG-OLD-1',
            'submitted_at' => '2026-10-01 09:30:00',
        ]);
    }
}
