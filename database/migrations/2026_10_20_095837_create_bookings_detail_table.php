<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Booking detail rows, one per booking (unique booking_id), split out of the bookings table:
 *
 *   booking_events     what the event is
 *   booking_schedules  when it happens (event_end_date is required)
 *   booking_payments   how it is paid (state drives the approval workflow)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->string('event_name');
            $table->string('event_type');
            $table->string('event_type_other')->nullable();
            $table->string('event_location');
            $table->string('event_contact_person')->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->string('venue_type')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->date('event_start_date')->index();
            $table->date('event_end_date');
            $table->string('event_start_time')->nullable();
            $table->string('event_end_time')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->string('payment_method')->nullable();
            $table->string('payment_state')->nullable()->index();
            $table->string('payment_reference')->nullable();
            $table->string('payment_receipt_path')->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
        Schema::dropIfExists('booking_schedules');
        Schema::dropIfExists('booking_events');
    }
};