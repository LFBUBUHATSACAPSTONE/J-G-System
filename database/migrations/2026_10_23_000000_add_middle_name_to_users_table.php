<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The sign-up, profile and booking forms all collect a middle name, while the original
 * users table only had the one-letter middle_initial. Keep both: middle_name stores what
 * the client typed, middle_initial stays in sync for the admin pages and older data.
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
