<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update only packages that still have their previous seeded price, preserving
     * any price that an admin has customized in the meantime.
     */
    public function up(): void
    {
        $prices = [
            'budget-lite' => [5000, 7000],
            'budget-party' => [10000, 15000],
            'budget-wedding' => [18000, 20000],
        ];

        foreach ($prices as $packageId => [$previous, $initial]) {
            DB::table('packages')
                ->where('id', $packageId)
                ->where('price', $previous)
                ->update(['price' => $initial]);
        }
    }

    public function down(): void
    {
        $prices = [
            'budget-lite' => [7000, 5000],
            'budget-party' => [15000, 10000],
            'budget-wedding' => [20000, 18000],
        ];

        foreach ($prices as $packageId => [$initial, $previous]) {
            DB::table('packages')
                ->where('id', $packageId)
                ->where('price', $initial)
                ->update(['price' => $previous]);
        }
    }
};
