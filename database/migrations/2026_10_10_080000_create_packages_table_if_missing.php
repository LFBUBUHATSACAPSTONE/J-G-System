<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('packages')) {
            return;
        }

        Schema::create('packages', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->boolean('available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('features')->nullable();
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

        if ($packages) {
            DB::table('packages')->insert($packages);
        }
    }

    public function down(): void
    {
        throw new LogicException('This migration is intentionally irreversible to preserve package data.');
    }
};
