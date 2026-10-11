<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->string('reference')->unique();
                $table->string('status')->index();
                $table->string('client_name');
                $table->string('client_email');
                $table->string('event_name')->nullable();
                $table->string('event_type')->nullable();
                $table->string('event_location')->nullable();
                $table->date('event_start_date')->index();
                $table->date('event_end_date')->nullable()->index();
                $table->string('event_start_time')->nullable();
                $table->string('event_end_time')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('payment_state')->nullable();
                $table->timestamp('submitted_at')->nullable()->index();
                $table->string('package_id')->nullable()->index();
                $table->string('package_name');
                $table->decimal('package_price', 10, 2)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('booking_capacity_locks')) {
            Schema::create('booking_capacity_locks', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
            });
        }

        DB::table('booking_capacity_locks')->insertOrIgnore(['id' => 1]);
    }

    public function down(): void
    {
        throw new LogicException('This migration is intentionally irreversible to preserve booking data.');
    }
};
