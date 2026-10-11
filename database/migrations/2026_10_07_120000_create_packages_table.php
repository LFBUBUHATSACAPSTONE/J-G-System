<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name', 100)->unique();
                $table->unsignedInteger('price');
                $table->boolean('available')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('features');
                $table->timestamps();
            });
        }

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

        if ($packages) {
            DB::table('packages')->insertOrIgnore($packages);
        }
    }

    public function down(): void
    {
        // Package data is business data and must survive migration rollbacks.
    }
};
