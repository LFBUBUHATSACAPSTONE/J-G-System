<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->string('id', 120)->primary();
            $table->string('name', 100)->unique();
            $table->unsignedInteger('price');
            $table->boolean('available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('features');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Package data is business data and must survive migration rollbacks.
    }
};
