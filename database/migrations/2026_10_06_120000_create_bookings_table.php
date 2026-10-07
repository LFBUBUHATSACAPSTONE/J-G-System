<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('status')->default('pending')->index();

            $table->string('client_name');
            $table->string('client_email')->index();
            $table->string('client_phone')->nullable();
            $table->text('client_address')->nullable();

            $table->string('event_name');
            $table->string('event_type');
            $table->string('event_type_other')->nullable();
            $table->string('event_location');
            $table->string('event_contact_person')->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->string('venue_type')->nullable();
            $table->date('event_start_date')->index();
            $table->date('event_end_date');
            $table->string('event_start_time')->nullable();
            $table->string('event_end_time')->nullable();

            $table->string('package_id')->nullable()->index();
            $table->string('package_name');
            $table->decimal('package_price', 10, 2)->nullable();

            $table->string('payment_method')->nullable();
            $table->string('payment_state')->nullable()->index();
            $table->string('payment_reference')->nullable();
            $table->string('payment_receipt_path')->nullable();

            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
