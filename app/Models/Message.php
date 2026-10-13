<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'body',
        'attachments',
        'read_at',
        'reply_to_id',
        'edited_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // The quoted parent message. Soft-deleted parents fall outside the SoftDeletes
    // scope, so a reply to an unsent message simply renders without its quote.
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isFromAdmin(): bool
    {
        return $this->sender_type === 'admin';
    }

    public function isFromClient(): bool
    {
        return $this->sender_type === 'client';
    }

    public function isEdited(): bool
    {
        return ! is_null($this->edited_at);
    }
}
