<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'unread_count',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at')->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Unread messages for one side of the chat: messages SENT BY the other side
     * whose read_at is still null. read_at is the single source of truth, so the
     * legacy unread_count column (an all-reads counter) can never disagree.
     */
    public function unreadCountFor(string $viewer): int
    {
        $other = $viewer === 'admin' ? 'client' : 'admin';

        if ($this->relationLoaded('messages')) {
            return $this->messages
                ->where('sender_type', $other)
                ->whereNull('read_at')
                ->count();
        }

        return $this->messages()
            ->where('sender_type', $other)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark everything the OTHER side sent as read for $viewer. Opening the chat
     * on either side only clears that side's unread; it never clears the other
     * side's badge or the shared unread_count column.
     */
    public function markReadFor(string $viewer): void
    {
        $other = $viewer === 'admin' ? 'client' : 'admin';

        $this->messages()
            ->where('sender_type', $other)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($viewer === 'admin') {
            $this->update(['unread_count' => 0]);
        }
    }

    public function incrementUnread(): void
    {
        $this->increment('unread_count');
    }

    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);
    }
}