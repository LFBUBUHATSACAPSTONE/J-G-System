<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('package_price', 10, 2)->nullable();
        });

        DB::table('bookings')
            ->leftJoin('packages', 'bookings.package_id', '=', 'packages.id')
            ->whereNull('bookings.package_price')
            ->whereNotNull('packages.price')
            ->select(['bookings.id', 'packages.price as current_package_price'])
            ->chunkById(500, function ($bookings): void {
                foreach ($bookings as $booking) {
                    DB::table('bookings')
                        ->where('id', $booking->id)
                        ->update(['package_price' => $booking->current_package_price]);
                }
            }, 'bookings.id', 'id');
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('package_price');
        });
    }
};
