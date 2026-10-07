<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings') && ! Schema::hasColumn('bookings', 'payment_method')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('payment_method')->nullable()->after('event_end_time');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'payment_method')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('payment_method');
            });
        }
    }
};
