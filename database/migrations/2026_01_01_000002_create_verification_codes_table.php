<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-time codes for sign-up and password reset (App\Models\VerificationCode).
 *
 *   pending_data  the encrypted sign-up payload (name/email/phone/password) held until the code
 *                 is confirmed, so no user row exists before the email is verified
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('identifier');
            $table->string('context');
            $table->string('code_hash');
            $table->longText('pending_data')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['identifier', 'context']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};
