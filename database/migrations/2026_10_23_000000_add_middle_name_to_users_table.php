<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The sign-up, profile and booking forms collect a middle name. This migration adds the
 * full-name field to accounts created under the original one-letter middle-initial schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'middle_name')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('middle_name', 100)->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'middle_name')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('middle_name');
        });
    }
};
