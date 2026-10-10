<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingPaymentMethodMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
    }

    public function test_migration_adds_payment_method_to_existing_bookings_table(): void
    {
        Schema::create('bookings', function ($table): void {
            $table->id();
            $table->string('event_end_time')->nullable();
        });

        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_10_07_070000_add_payment_method_to_bookings_table.php'),
            '--realpath' => true,
            '--force' => true,
        ]);

        $this->assertTrue(Schema::hasColumn('bookings', 'payment_method'));

        DB::table('bookings')->insert([
            'payment_method' => 'GCash',
        ]);
        $this->assertDatabaseHas('bookings', ['payment_method' => 'GCash']);
    }
}
