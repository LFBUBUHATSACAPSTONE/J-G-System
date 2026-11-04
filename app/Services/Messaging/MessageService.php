<?php

namespace App\Services\Messaging;

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
            // Set when the sender unsent the message. The live feed tells the reader to
            // drop it from the thread; the row itself is kept (soft delete).
            'deleted' => $message->trashed(),
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
        return [
            'unread' => $conversation->unreadCountFor('client'),
            'items' => $this->timeline($conversation, true),
        ];
    }

    /**
     * Messages and approved-booking cards in ONE chronological list. Every item carries
     * `at`: a booking card is pinned to the instant its booking was approved, so once it
     * has shown up it stays put and later messages simply appear after it. A stable sort
     * (PHP 8) keeps messages before a card that shares their exact timestamp.
     */
    private function timeline(Conversation $conversation, bool $withMessages): array
    {
        $items = [];

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

        foreach ($this->bookingItems($conversation) as $item) {
            $items[] = $item;
        }

        usort($items, fn (array $a, array $b) => $a['at'] <=> $b['at']);

        return $items;
    }

    public function formatConversationForAdmin(Conversation $conversation, bool $withMessages = false): array
    {
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
            'items' => $this->timeline($conversation, $withMessages),
        ];
    }

    /**
     * Booking cards for a thread. The conversation only supplies the client id and its
     * own linked booking; the rules live in bookingCards().
     */
    private function bookingItems(Conversation $conversation): array
    {
        return $this->bookingCards($conversation->user_id, $conversation->booking);
    }

    /**
     * Booking cards for a client, independent of any conversation: the client's bookings
     * in the reflected statuses (approved by default) plus an optional linked booking, as
     * long as it too is reflected. Each card's `at` is the instant the booking was
     * approved, so it slots into the timeline there and never moves. Oldest first, so
     * among cards the latest approval is last.
     *
     * Public and conversation-free on purpose: a client who has booked but has not
     * messaged yet has no thread, and their own chat still needs to show the bookings.
     */
    public function bookingCards(?int $userId, ?Booking $linked = null): array
    {
        $statuses = $this->reflectedStatuses();

        $bookings = collect();

        if ($userId) {
            $bookings = Booking::query()
                ->where('user_id', $userId)
                // An empty status list means "every status"; the default only reflects
                // approved bookings, which is what the chat is meant to surface.
                ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
                ->with(['eventDetails', 'schedule', 'payment', 'package'])
                ->orderBy('approved_at')
                ->orderBy('id')
                ->get();
        }

        if ($linked
            && ($statuses === [] || in_array($linked->status, $statuses, true))
            && ! $bookings->contains(fn (Booking $booking) => $booking->id === $linked->id)) {
            $bookings->push($linked);
        }

        return $bookings->map(fn (Booking $booking) => [
            'type' => 'booking',
            'at' => $this->approvedAt($booking),
            'booking' => $booking->toAdminArray(),
        ])->all();
    }

    /** The configured booking statuses that get a card; [] means "no filter". */
    private function reflectedStatuses(): array
    {
        $statuses = config('admin.messages.reflected_booking_statuses');

        return is_array($statuses) ? array_values($statuses) : [];
    }

    /**
     * The instant a booking's card belongs at in the thread. approved_at is stamped once,
     * when the booking is approved, and never rewritten, so the card never moves.
     */
    private function approvedAt(Booking $booking): Carbon
    {
        return $booking->approved_at ?? $booking->updated_at ?? $booking->created_at ?? now();
    }

    /**
     * A fresh query for bookings that get a card: the reflected statuses (approved by
     * default) and, defensively, a set approved_at. Callers add their own filters.
     */
    private function newlyApprovedBookings(): \Illuminate\Database\Eloquent\Builder
    {
        $statuses = $this->reflectedStatuses();

        return Booking::query()
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->whereNotNull('approved_at');
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
     * Messages that changed (edited or unsent) at or after `since`, for the live feeds.
     * A soft delete bumps `updated_at` too, so one cursor covers both. Only messages the
     * reader already has (`id <= $after`) are returned: brand new ones travel on the
     * normal `messages` feed, and `id <= $after` keeps this feed purely edits/unsends.
     *
     * Pass a conversation id to scope it to one thread, or null for every conversation
     * (the admin side). Nothing is returned without a cursor.
     */
    public function changedMessages(?int $conversationId, ?Carbon $since, int $after): array
    {
        if (! $since || $after < 1) {
            return [];
        }

        return Message::withTrashed()
            ->with('replyTo')
            ->when($conversationId, fn ($query) => $query->where('conversation_id', $conversationId))
            ->where('updated_at', '>=', $since)
            ->where('id', '<=', $after)
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message) => $this->formatMessage($message))
            ->all();
    }

    /**
     * The cursor that pairs with changedMessages(). Whole-second precision matches the
     * `updated_at` column, so an edit or unsend made in the boundary second is re-sent on
     * the next poll (idempotent) instead of being skipped.
     */
    public function changedCursor(): string
    {
        return now()->startOfSecond()->toIso8601String();
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
            'at' => $this->approvedAt($booking)->toIso8601String(),
            'html' => view('components.chat.booking-card', [
                'booking' => $booking->toAdminArray(),
                'admin' => $admin,
            ])->render(),
        ];
    }

    /**
     * A client's bookings APPROVED after `since` pop into their thread live. Keyed on
     * approved_at, not updated_at, so a later edit to an approved booking does not make
     * its (already placed) card pop up again.
     */
    public function updatedBookingCardsForUser(int $userId, ?Carbon $since): array
    {
        if (! $since) {
            return [];
        }

        return $this->newlyApprovedBookings()
            ->where('user_id', $userId)
            ->where('approved_at', '>', $since)
            ->with(['eventDetails', 'schedule', 'payment', 'package'])
            ->orderBy('approved_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Booking $booking) => $this->formatBookingCard($booking))
            ->all();
    }

    /**
     * The admin mirror: any client's booking approved after `since`, tagged with the
     * conversation whose panel should show it. Bookings with no conversation yet are
     * skipped: there is no panel to drop the card into.
     */
    public function updatedBookingCardsForAdmin(?Carbon $since): array
    {
        if (! $since) {
            return [];
        }

        $bookings = $this->newlyApprovedBookings()
            ->where('approved_at', '>', $since)
            ->with(['eventDetails', 'schedule', 'payment', 'package'])
            ->orderBy('approved_at')
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
