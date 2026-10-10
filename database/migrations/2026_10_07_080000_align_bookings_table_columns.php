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
    }

    public function down(): void
    {
        throw new LogicException('This migration is intentionally irreversible to preserve booking data.');
    }
};
