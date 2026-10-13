<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The chat pins an approved booking's card at the instant it was approved and never
// moves it again, so it needs a real approval timestamp. Additive only: no table or row
// is removed, and existing approved bookings are backfilled from their last write (the
// approval was normally that write) so their cards keep a sensible position.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'approved_at')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('submitted_at');
            });
        }

        DB::table('bookings')
            ->where('status', 'approved')
            ->whereNull('approved_at')
            ->update(['approved_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'approved_at')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }
    }
};
