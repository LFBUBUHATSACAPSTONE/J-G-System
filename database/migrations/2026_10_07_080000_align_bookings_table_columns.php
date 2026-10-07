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
            return;
        }

        if (! Schema::hasColumn('bookings', 'submitted_at')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->timestamp('submitted_at')->nullable()->index();
            });
        }

        if (Schema::hasColumn('bookings', 'created_at')) {
            DB::table('bookings')
                ->whereNull('submitted_at')
                ->update(['submitted_at' => DB::raw('created_at')]);
        }

        $obsoleteColumns = array_intersect(
            [
                'client_phone',
                'client_address',
                'venue_contact_person',
                'guest_count',
                'venue_type',
                'payment_label',
                'payment_status',
            ],
            Schema::getColumnListing('bookings')
        );

        if ($obsoleteColumns !== []) {
            Schema::table('bookings', function (Blueprint $table) use ($obsoleteColumns) {
                $table->dropColumn($obsoleteColumns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('client_phone')->nullable();
            $table->string('client_address')->nullable();
            $table->string('venue_contact_person')->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->string('venue_type')->nullable();
            $table->string('payment_label')->nullable();
            $table->string('payment_status')->nullable();
        });

        if (Schema::hasColumn('bookings', 'submitted_at')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('submitted_at');
            });
        }
    }
};
