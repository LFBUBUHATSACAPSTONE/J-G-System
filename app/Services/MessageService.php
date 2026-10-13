<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class MessageService
{
    public function formatMessage(Message $message): array
    {
        return [
            'id' => $message->id,
            'type' => 'message',
            'from' => $message->sender_type,
            'body' => $message->body,
            'at' => $message->created_at,
            'attachments' => $message->attachments ?? [],
        ];
    }

    public function formatConversationForUser(Conversation $conversation): array
    {
        $items = [];

        $booking = $conversation->booking;
        if ($booking) {
            $items[] = [
                'type' => 'booking',
                'at' => $booking->submitted_at ?? $booking->created_at,
                'booking' => $booking->toAdminArray(),
            ];
        }

        foreach ($conversation->messages as $message) {
            $items[] = $this->formatMessage($message);
        }

        return [
            'unread' => $conversation->unreadCountFor('client'),
            'items' => $items,
        ];
    }

    public function formatConversationForAdmin(Conversation $conversation, bool $withMessages = false): array
    {
        $items = [];

        $booking = $conversation->booking;
        if ($booking) {
            $items[] = [
                'type' => 'booking',
                'at' => $booking->submitted_at ?? $booking->created_at,
                'booking' => $booking->toAdminArray(),
            ];
        }

        if ($withMessages) {
            foreach ($conversation->messages as $message) {
                $items[] = $this->formatMessage($message);
            }
        } else {
            $lastMessage = $conversation->latestMessage;
            if ($lastMessage) {
                $items[] = $this->formatMessage($lastMessage);
            }
        }

        // The account can be soft-deleted, so `user` may be null here. Never read a
        // property off it directly: fall back to the booking's client snapshot.
        $user = $conversation->user;
        $fullName = $user ? trim($user->first_name . ' ' . $user->last_name) : '';

        return [
            'id' => $conversation->id,
            'unread' => $conversation->unreadCountFor('admin'),
            'client' => [
                'name' => $fullName !== ''
                    ? $fullName
                    : ($booking?->client_name ?: 'Deleted client'),
                'phone' => $user?->phone ?: $booking?->client_phone,
            ],
            'items' => $items,
        ];
    }

    /**
     * Unread NEW messages in a user's conversations: every admin reply the client
     * has not opened yet. Drives the chat bubble and sidebar badges.
     */
    public function unreadCountForUser(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return Message::query()
            ->where('sender_type', 'admin')
            ->whereNull('read_at')
            ->whereHas('conversation', fn ($query) => $query->where('user_id', $user->id))
            ->count();
    }

    /**
     * Unread NEW messages for the admin side: every client message no admin has
     * opened yet. Drives the admin sidebar badge.
     */
    public function unreadCountForAdmin(): int
    {
        return Message::query()
            ->where('sender_type', 'client')
            ->whereNull('read_at')
            ->count();
    }

    public function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' bytes';
    }
}