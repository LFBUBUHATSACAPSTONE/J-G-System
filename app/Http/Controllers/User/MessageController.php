<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function __construct(private readonly MessageService $messages)
    {
    }

    public function index(): View
    {
        $user = auth()->user();
        $thread = $this->threadFor($user, markRead: true);

        return view('user.account.messages', [
            'conversation' => $thread,
        ]);
    }

    public function indexJson(): JsonResponse
    {
        $user = auth()->user();
        $thread = $this->threadFor($user, markRead: true);

        return response()->json([
            'conversation' => $thread,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'message' => 'Please log in to send a message.',
            ], 401);
        }

        $conversation = $this->findConversation($user, true);

        $att = config('admin.messages.attachments');

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:' . config('admin.messages.max_length'), 'required_without:attachments'],
            'attachments' => ['nullable', 'array', 'max:' . $att['max_files']],
            'attachments.*' => ['file', 'mimes:' . $att['mimes'], 'max:' . $att['max_kb']],
            'reply_to_id' => ['nullable', 'integer'],
        ]);

        $attachmentData = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                // Read the upload before storing it: `store()` moves the temp file away.
                $isImage = str_starts_with((string) $file->getMimeType(), 'image/');
                $name = $file->getClientOriginalName();
                $size = $this->messages->formatFileSize($file->getSize());
                $path = $file->store('messages', 'public');

                $attachmentData[] = [
                    'name' => $name,
                    'url' => Storage::url($path),
                    // Images preview inline for both sides; everything else is a file link.
                    'kind' => $isImage ? 'image' : 'file',
                    'size' => $size,
                ];
            }
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'client',
            'sender_id' => $user->id,
            'body' => $validated['body'] ?? null,
            'attachments' => $attachmentData ?: null,
            'reply_to_id' => $this->replyTarget($conversation, $validated['reply_to_id'] ?? null),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => $conversation->unread_count + 1,
        ]);

        return response()->json([
            'ok' => true,
            'message' => $this->messages->formatMessage($message),
        ]);
    }

    /**
     * Edit a message the client already sent. Only their own client messages in their
     * own thread can be edited; the body is replaced and the "edited" flag is stamped.
     */
    public function update(Request $request, Message $message): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Please log in to edit a message.'], 401);
        }

        if (! $this->ownsClientMessage($user, $message)) {
            return response()->json(['message' => 'You can only edit your own messages.'], 403);
        }

        if (! $message->isEditable()) {
            return response()->json(['message' => 'The editing window for this message has passed.'], 403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:' . config('admin.messages.max_length')],
        ]);

        $message->update([
            'body' => $validated['body'],
            'edited_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => $this->messages->formatMessage($message->fresh()),
        ]);
    }

    /**
     * "Unsend" a message the client already sent. Soft delete: the row is kept and only
     * leaves the thread, so no message data is destroyed.
     */
    public function destroy(Message $message): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Please log in to unsend a message.'], 401);
        }

        if (! $this->ownsClientMessage($user, $message)) {
            return response()->json(['message' => 'You can only unsend your own messages.'], 403);
        }

        if (! $message->isEditable()) {
            return response()->json(['message' => 'The editing window for this message has passed.'], 403);
        }

        $conversation = $message->conversation;
        $message->delete();

        $this->touchConversation($conversation);

        return response()->json(['ok' => true, 'id' => $message->id]);
    }

    /**
     * The landing chat widget marks the client's side read the moment the panel
     * opens, so the unread bubble disappears and stays gone.
     */
    public function markRead(): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Please log in to message us.'], 401);
        }

        $conversation = $this->findConversation($user);

        if ($conversation) {
            $conversation->markReadFor('client');
        }

        return response()->json(['ok' => true, 'unread' => 0]);
    }

    /**
     * Light polling feed for the open chat: the total unread count (for badges)
     * plus any admin replies newer than `after` in the active conversation.
     * Never fails the page: an empty payload is a valid "nothing new" answer.
     */
    public function updates(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Please log in to message us.'], 401);
        }

        $after = max(0, (int) $request->query('after', 0));
        $since = $this->messages->since($request->query('since'));
        $syncedAt = now();
        $bookings = $this->messages->updatedBookingCardsForUser($user->id, $since);
        $conversation = $this->findConversation($user);
        $unread = $this->messages->unreadCountForUser($user);

        if (! $conversation) {
            return response()->json([
                'unread' => $unread,
                'conversation_id' => null,
                'messages' => [],
                'bookings' => $bookings,
                'bookings_synced_at' => $syncedAt->toIso8601String(),
            ]);
        }

        $messages = $conversation->messages()
            ->with('replyTo')
            ->where('id', '>', $after)
            ->where('sender_type', 'admin')
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message) => $this->messages->formatMessage($message));

        return response()->json([
            'unread' => $unread,
            'conversation_id' => $conversation->id,
            'messages' => $messages,
            'bookings' => $bookings,
            'bookings_synced_at' => $syncedAt->toIso8601String(),
        ]);
    }

    private function threadFor($user, bool $markRead): array
    {
        $conversation = $this->findConversation($user);

        if (! $conversation) {
            // No thread yet, but the client may already have bookings: still show the
            // booking details in their own chat, before they ever send a message.
            return ['unread' => 0, 'items' => $this->messages->bookingCards($user?->id)];
        }

        if ($markRead) {
            $conversation->markReadFor('client');
            $conversation->load('messages.replyTo');
        }

        return $this->messages->formatConversationForUser($conversation);
    }

    // The message being replied to must belong to the same conversation; anything else
    // is ignored so a crafted id can never leak another thread's content.
    private function replyTarget(Conversation $conversation, $id): ?int
    {
        if (! $id) {
            return null;
        }

        return $conversation->messages()->whereKey($id)->exists() ? (int) $id : null;
    }

    private function ownsClientMessage($user, Message $message): bool
    {
        return $message->sender_type === 'client'
            && (int) $message->sender_id === (int) $user->id
            && $message->conversation
            && (int) $message->conversation->user_id === (int) $user->id;
    }

    // After an unsend, point the conversation at the newest surviving message so the
    // chat list keeps the right ordering (the column is not used for unread badges).
    private function touchConversation(?Conversation $conversation): void
    {
        if (! $conversation) {
            return;
        }

        $last = $conversation->messages()->latest('created_at')->latest('id')->first();

        if ($last) {
            $conversation->update(['last_message_at' => $last->created_at]);
        }
    }

    private function findConversation($user, bool $create = false): ?Conversation
    {
        if (! $user) {
            return null;
        }

        // The user side always shows ONE active thread (the latest), so a user's
        // history can never split across conversations. If several exist, sending
        // reuses the latest and keeps the whole story in one place.
        $conversation = Conversation::with(['booking.eventDetails', 'booking.schedule', 'booking.payment', 'booking.package'])
            ->where('user_id', $user->id)
            ->latest('last_message_at')
            ->latest('id')
            ->first();

        if ($conversation || ! $create) {
            return $conversation;
        }

        $latestBooking = Booking::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        return Conversation::create([
            'user_id' => $user->id,
            'booking_id' => $latestBooking?->id,
            'last_message_at' => now(),
        ]);
    }
}