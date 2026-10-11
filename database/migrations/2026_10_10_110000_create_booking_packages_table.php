<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_packages')) {
            Schema::create('booking_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->unique()->constrained('bookings')->restrictOnDelete();
                $table->string('package_id')->nullable();
                $table->string('package_name');
                $table->decimal('package_price', 10, 2)->nullable();
                $table->timestamps();

                $table->foreign('package_id')->references('id')->on('packages')->restrictOnDelete();
            });
        }

        DB::table('bookings')->orderBy('id')->chunk(500, function ($bookings): void {
            $packageIds = $bookings->pluck('package_id')->filter()->unique()->values();
            $existingPackageIds = DB::table('packages')
                ->whereIn('id', $packageIds)
                ->pluck('id')
                ->all();

            $rows = $bookings->map(fn ($booking) => [
                'booking_id' => $booking->id,
                'package_id' => in_array($booking->package_id, $existingPackageIds, true)
                    ? $booking->package_id
                    : null,
                'package_name' => $booking->package_name,
                'package_price' => $booking->package_price,
                'created_at' => $booking->created_at ?? now(),
                'updated_at' => $booking->updated_at ?? now(),
            ])->all();

            DB::table('booking_packages')->insertOrIgnore($rows);
        });
    }

    public function down(): void
    {
        throw new LogicException('This migration is intentionally irreversible to preserve booking data.');
    }
};
