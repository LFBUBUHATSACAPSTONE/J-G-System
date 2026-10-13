<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reply / edit / unsend support. Every column is nullable and additive: existing
// messages keep their values untouched, nothing is dropped or rewritten.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // The message this one replies to (self-reference). Null = a normal message.
            if (! Schema::hasColumn('messages', 'reply_to_id')) {
                $table->unsignedBigInteger('reply_to_id')->nullable()->index();
            }

            // When the body was last changed. Null = never edited.
            if (! Schema::hasColumn('messages', 'edited_at')) {
                $table->timestamp('edited_at')->nullable();
            }

            // Soft "unsend": the row is kept (no data is deleted), it only leaves the thread.
            if (! Schema::hasColumn('messages', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            foreach (['reply_to_id', 'edited_at', 'deleted_at'] as $column) {
                if (Schema::hasColumn('messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
