<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Client bookings. The event, schedule and payment fields live in their own tables
 * (see the next migration) and are reached through the relations on App\Models\Booking.
 *
 *   user_id             the account that booked, when the client was signed in (My Bookings)
 *   rescheduled_from_id the original booking when this row is a reschedule
 *   submitted_at        when the client submitted it (chat + dashboard ordering)
 *   approved_at         stamped the moment the status changes to "approved"; the chat pins the
 *                       booking card at this instant and never moves it again
 *   booking_capacity_locks  a single row (id = 1) used to serialise capacity checks on approval
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('pending')->index();

            $table->string('client_name');
            $table->string('client_email')->index();
            $table->string('client_phone')->nullable();
            $table->text('client_address')->nullable();

            $table->string('package_id')->nullable();
            $table->foreign('package_id')->references('id')->on('packages')->restrictOnDelete();

            $table->unsignedBigInteger('rescheduled_from_id')->nullable()->unique();
            $table->foreign('rescheduled_from_id')->references('id')->on('bookings')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('approved_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('booking_capacity_locks', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
        });

        DB::table('booking_capacity_locks')->insertOrIgnore(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_capacity_locks');
        Schema::dropIfExists('bookings');
    }
};
