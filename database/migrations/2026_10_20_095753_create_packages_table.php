<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The service packages (string slug primary key: "budget-lite", "luxe-lite", ...).
 *
 * The rows are seeded here from config/packages.php so a fresh migrate reproduces the catalogue.
 * Admin > Packages edits them in place afterwards (App\Http\Controllers\Admin\PackageController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name', 100)->unique();
            $table->unsignedInteger('price');
            $table->boolean('available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('features');
            $table->timestamps();
        });

        $timestamp = now();
        $packages = [];

        foreach (config('packages', []) as $id => $package) {
            $packages[] = [
                'id' => $id,
                'name' => $package['name'],
                'price' => $package['price'],
                'available' => true,
                'sort_order' => count($packages),
                'features' => json_encode($package['features']),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if ($packages !== []) {
            DB::table('packages')->insertOrIgnore($packages);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};