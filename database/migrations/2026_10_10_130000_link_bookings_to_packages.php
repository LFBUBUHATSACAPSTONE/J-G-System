<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasTable('packages')) {
            throw new RuntimeException('Cannot link bookings to packages: required tables are missing.');
        }

        if (! Schema::hasColumn('bookings', 'package_id')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->string('package_id')->nullable()->index();
            });
        }

        if (Schema::hasTable('booking_packages')) {
            DB::table('booking_packages')
                ->join('packages', 'packages.id', '=', 'booking_packages.package_id')
                ->select('booking_packages.booking_id', 'booking_packages.package_id')
                ->orderBy('booking_packages.booking_id')
                ->chunk(500, function ($packageLinks): void {
                    foreach ($packageLinks as $packageLink) {
                        DB::table('bookings')
                            ->where('id', $packageLink->booking_id)
                            ->whereNull('package_id')
                            ->update(['package_id' => $packageLink->package_id]);
                    }
                });
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreign('package_id')
                ->references('id')
                ->on('packages')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new LogicException('This migration is intentionally irreversible to preserve booking data.');
    }
};
