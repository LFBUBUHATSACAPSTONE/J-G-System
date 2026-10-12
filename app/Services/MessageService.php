<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;

class MessageService
{
    public function formatMessage(Message $message): array
    {
        return [
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
            'unread' => $conversation->unread_count,
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

        return [
            'id' => $conversation->id,
            'unread' => $conversation->unread_count,
            'client' => [
                'name' => trim($conversation->user->first_name . ' ' . $conversation->user->last_name),
                'phone' => $conversation->user->phone,
            ],
            'items' => $items,
        ];
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