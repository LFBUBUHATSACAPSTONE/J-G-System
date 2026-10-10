<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_events')) {
            Schema::create('booking_events', function (Blueprint $table) {
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
        }

        if (! Schema::hasTable('booking_schedules')) {
            Schema::create('booking_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->unique()->constrained('bookings')->restrictOnDelete();
                $table->date('event_start_date')->index();
                $table->date('event_end_date');
                $table->string('event_start_time')->nullable();
                $table->string('event_end_time')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('booking_payments')) {
            Schema::create('booking_payments', function (Blueprint $table) {
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

        $hasPaymentAmount = Schema::hasColumn('bookings', 'payment_amount');

        DB::table('bookings')->orderBy('id')->chunk(500, function ($bookings) use ($hasPaymentAmount): void {
            $now = now();
            $events = [];
            $schedules = [];
            $payments = [];

            foreach ($bookings as $booking) {
                $timestamps = [
                    'created_at' => $booking->created_at ?? $now,
                    'updated_at' => $booking->updated_at ?? $now,
                ];

                $events[] = array_merge([
                    'booking_id' => $booking->id,
                    'event_name' => $booking->event_name,
                    'event_type' => $booking->event_type,
                    'event_type_other' => $booking->event_type_other,
                    'event_location' => $booking->event_location,
                    'event_contact_person' => $booking->event_contact_person,
                    'guest_count' => $booking->guest_count,
                    'venue_type' => $booking->venue_type,
                ], $timestamps);

                $schedules[] = array_merge([
                    'booking_id' => $booking->id,
                    'event_start_date' => $booking->event_start_date,
                    'event_end_date' => $booking->event_end_date,
                    'event_start_time' => $booking->event_start_time,
                    'event_end_time' => $booking->event_end_time,
                ], $timestamps);

                $payments[] = array_merge([
                    'booking_id' => $booking->id,
                    'payment_method' => $booking->payment_method,
                    'payment_state' => $booking->payment_state,
                    'payment_reference' => $booking->payment_reference,
                    'payment_receipt_path' => $booking->payment_receipt_path,
                    'payment_amount' => $hasPaymentAmount ? $booking->payment_amount : null,
                ], $timestamps);
            }

            DB::table('booking_events')->insertOrIgnore($events);
            DB::table('booking_schedules')->insertOrIgnore($schedules);
            DB::table('booking_payments')->insertOrIgnore($payments);
        });
    }

    public function down(): void
    {
        throw new LogicException('This migration is intentionally irreversible to preserve booking data.');
    }
};
