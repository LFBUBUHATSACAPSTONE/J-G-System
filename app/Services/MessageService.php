<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MessageService
{
    public function formatMessage(Message $message): array
    {
        return [
            'id' => $message->id,
            'type' => 'message',
            'conversation_id' => $message->conversation_id,
            'from' => $message->sender_type,
            'body' => $message->body,
            'at' => $message->created_at,
            'edited' => $message->isEdited(),
            // Edit/unsend window for the sender: the client hides the buttons once this
            // moment passes and both controllers refuse a late change.
            'editable' => $message->isEditable(),
            'edit_expires_at' => $message->editableUntil()?->toIso8601String(),
            'reply_to' => $this->formatReply($message),
            'attachments' => $this->formatAttachments($message->attachments ?? []),
        ];
    }

    public function formatConversationForUser(Conversation $conversation): array
    {
        $items = $this->bookingItems($conversation);

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
        $items = $this->bookingItems($conversation);

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
        $booking = $conversation->booking;
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
     * Booking cards shown at the top of a thread on BOTH sides: every booking the client
     * owns whose status is listed in config('admin.messages.reflected_booking_statuses')
     * (approved by default), plus the conversation's own linked booking whatever its
     * status — so an in-progress request still shows. Deduplicated by id.
     */
    private function bookingItems(Conversation $conversation): array
    {
        $statuses = config('admin.messages.reflected_booking_statuses', ['approved']);

        $bookings = collect();

        if ($conversation->user_id && ! empty($statuses)) {
            $bookings = Booking::query()
                ->where('user_id', $conversation->user_id)
                ->whereIn('status', $statuses)
                ->with(['eventDetails', 'schedule', 'payment', 'package'])
                ->orderBy('submitted_at')
                ->orderBy('id')
                ->get();
        }

        $linked = $conversation->booking;
        if ($linked && ! $bookings->contains(fn (Booking $booking) => $booking->id === $linked->id)) {
            $bookings->push($linked);
        }

        return $bookings->map(fn (Booking $booking) => [
            'type' => 'booking',
            'at' => $booking->submitted_at ?? $booking->created_at,
            'booking' => $booking->toAdminArray(),
        ])->all();
    }

    /**
     * Parse the `since` cursor the chat poll sends back. Anything unparseable means
     * "no cursor", which yields an EMPTY booking feed rather than replaying every
     * booking the client has ever made.
     */
    public function since($value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * A booking card for the live feed: the same Blade partial the page renders (so
     * the markup lives in one place), plus the id/reference/status the client needs
     * to slot it into the thread and de-duplicate it.
     */
    public function formatBookingCard(Booking $booking, bool $admin = false): array
    {
        return [
            'id' => $booking->id,
            'type' => 'booking',
            'reference' => $booking->reference,
            'status' => $booking->status,
            'at' => ($booking->updated_at ?? $booking->created_at)?->toIso8601String(),
            'html' => view('components.chat.booking-card', [
                'booking' => $booking->toAdminArray(),
                'admin' => $admin,
            ])->render(),
        ];
    }

    /**
     * Bookings a client OWNS that changed after `since`: a brand-new booking (any
     * status) and a later status change both pop into their thread live.
     */
    public function updatedBookingCardsForUser(int $userId, ?Carbon $since): array
    {
        if (! $since) {
            return [];
        }

        return Booking::query()
            ->where('user_id', $userId)
            ->where('updated_at', '>', $since)
            ->with(['eventDetails', 'schedule', 'payment', 'package'])
            ->orderBy('updated_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Booking $booking) => $this->formatBookingCard($booking))
            ->all();
    }

    /**
     * The admin mirror: any client's booking changed after `since`, tagged with the
     * conversation whose panel should show it. Bookings with no conversation yet are
     * skipped: there is no panel to drop the card into.
     */
    public function updatedBookingCardsForAdmin(?Carbon $since): array
    {
        if (! $since) {
            return [];
        }

        $bookings = Booking::query()
            ->where('updated_at', '>', $since)
            ->with(['eventDetails', 'schedule', 'payment', 'package'])
            ->orderBy('updated_at')
            ->orderBy('id')
            ->get();

        if ($bookings->isEmpty()) {
            return [];
        }

        // One query maps each client to the conversation the admin page is showing.
        $conversations = Conversation::query()
            ->whereIn('user_id', $bookings->pluck('user_id')->filter()->unique())
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($group) => $group->first()->id);

        return $bookings
            ->map(function (Booking $booking) use ($conversations) {
                $conversationId = $conversations->get($booking->user_id);

                if (! $conversationId) {
                    return null;
                }

                return $this->formatBookingCard($booking, true) + [
                    'conversation_id' => $conversationId,
                ];
            })
            ->filter()
            ->values()
            ->all();
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

    // A short, safe preview of the message being replied to (null when it is gone).
    private function formatReply(Message $message): ?array
    {
        $parent = $message->replyTo;

        if (! $parent) {
            return null;
        }

        return [
            'id' => $parent->id,
            'from' => $parent->sender_type,
            'body' => $this->replyExcerpt($parent),
        ];
    }

    private function replyExcerpt(Message $message): string
    {
        $body = trim((string) $message->body);

        if ($body !== '') {
            return Str::limit($body, 120);
        }

        return count($message->attachments ?? []) ? 'Attachment' : '';
    }

    private function formatAttachments(array $attachments): array
    {
        return array_map(function ($file) {
            if (is_array($file) && ! empty($file['url'])) {
                $file['url'] = $this->publicUrl((string) $file['url']);
            }

            return $file;
        }, $attachments);
    }

    /**
     * Rows written before the URL fix stored a root-relative "/storage/..." path. When
     * the app is served from a sub-directory (e.g. /J-G-System/public) that path 404s,
     * so rebuild it against the current request. Absolute external URLs are left alone.
     */
    private function publicUrl(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) {
            $path = (string) parse_url($url, PHP_URL_PATH);

            // Not a local storage URL? Leave it exactly as stored.
            if ($path === '' || ! str_contains($path, '/storage/')) {
                return $url;
            }

            $url = $path;
        }

        $relative = ltrim((string) preg_replace('#^/?storage/#i', '', $url), '/');

        if ($relative === '') {
            return $url;
        }

        return asset('storage/' . $relative);
    }
}
