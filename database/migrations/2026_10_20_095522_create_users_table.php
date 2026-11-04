<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts for BOTH sides of the system. There is a single users table and a single `web` guard;
 * which side an account may use is decided by the `role` column:
 *
 *   'user'  (default)  the customer area (landing, booking, account, messages)
 *   'admin'            the admin panel (/admin/*)
 *
 * Admin accounts are created by the owner (see DatabaseSeeder); sign-up always creates a 'user'.
 * Everything is in one place here so a fresh `php artisan migrate:fresh` reproduces the schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_initial', 1)->nullable();
            $table->string('email')->unique();
            $table->string('google_id')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->text('address')->nullable();

            // Which side of the system this account may use. Indexed because every guarded
            // request looks the signed-in user's role up.
            $table->string('role', 20)->default('user')->index();

            $table->string('password');
            $table->string('avatar_path')->nullable();
            $table->rememberToken();
            $table->timestamps();

            // "Delete account" is a soft delete: the row, the email and every booking stay.
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};