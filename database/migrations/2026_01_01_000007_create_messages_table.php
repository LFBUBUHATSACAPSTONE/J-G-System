<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat messages. `sender_type` says which side wrote it (client/admin); `sender_id` is the user
 * row behind an admin reply. Threading columns are additive:
 *
 *   reply_to_id  the message this one answers (self reference)
 *   edited_at    stamped when the body was last edited (edit is time-boxed in config/admin/messages.php)
 *   deleted_at   soft delete ("unsend"): the row stays, it is only hidden from the thread
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('sender_type', ['client', 'admin'])->default('client');
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->foreign('reply_to_id')->references('id')->on('messages')->nullOnDelete();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('reply_to_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
