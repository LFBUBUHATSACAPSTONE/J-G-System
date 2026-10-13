<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        // `withTrashed()` on the client: a deleted account keeps its message history
        // visible to the admin instead of turning the relation into null.
        $conversations = Conversation::with([
            'user' => fn ($query) => $query->withTrashed(),
            'booking.eventDetails', 'booking.schedule', 'booking.payment', 'booking.package', 'messages.replyTo',
        ])
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->get()
            // Full thread, not just the last message: opening a chat always shows the whole history.
            ->map(fn (Conversation $conversation) => $this->messages->formatConversationForAdmin($conversation, true));

        return view('admin.messages', ['conversations' => $conversations]);
    }

    public function show(int $id): JsonResponse
    {
        $conversation = Conversation::with([
            'user' => fn ($query) => $query->withTrashed(),
            'booking.eventDetails', 'booking.schedule', 'booking.payment', 'booking.package',
        ])
            ->findOrFail($id);

        // Opening a chat on the admin side only clears the client messages it was
        // waiting on; the client's own unread badges are never touched.
        $conversation->markReadFor('admin');
        $conversation->load('messages.replyTo');

        return response()->json([
            'conversation' => $this->messages->formatConversationForAdmin($conversation, true),
        ]);
    }

    /**
     * Live feed for the open admin page: every client message newer than `after`,
     * plus the current admin unread total. The admin page polls this so a client
     * message shows up without a refresh, exactly like the user side polls its own
     * feed for new admin replies.
     */
    public function updates(Request $request): JsonResponse
    {
        $after = max(0, (int) $request->query('after', 0));
        $since = $this->messages->since($request->query('since'));
        $syncedAt = now();

        $messages = Message::with('replyTo')
            ->where('sender_type', 'client')
            ->where('id', '>', $after)
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message) => $this->messages->formatMessage($message));

        return response()->json([
            'ok' => true,
            'unread' => $this->messages->unreadCountForAdmin(),
            'messages' => $messages,
            'bookings' => $this->messages->updatedBookingCardsForAdmin($since),
            'bookings_synced_at' => $syncedAt->toIso8601String(),
        ]);
    }

    public function store(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

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
            'sender_type' => 'admin',
            'sender_id' => auth()->id(),
            'body' => $validated['body'] ?? null,
            'attachments' => $attachmentData ?: null,
            'reply_to_id' => $this->replyTarget($conversation, $validated['reply_to_id'] ?? null),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => 0,
        ]);

        return response()->json([
            'ok' => true,
            'message' => $this->messages->formatMessage($message),
        ]);
    }

    /**
     * Edit an admin reply. Only admin messages can be edited from this side; the body is
     * replaced and the "edited" flag is stamped.
     */
    public function update(Request $request, Message $message): JsonResponse
    {
        if ($message->sender_type !== 'admin') {
            return response()->json(['message' => 'You can only edit replies you sent.'], 403);
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
     * "Unsend" an admin reply. Soft delete: the row is kept and only leaves the thread,
     * so no message data is destroyed.
     */
    public function destroy(Message $message): JsonResponse
    {
        if ($message->sender_type !== 'admin') {
            return response()->json(['message' => 'You can only unsend replies you sent.'], 403);
        }

        if (! $message->isEditable()) {
            return response()->json(['message' => 'The editing window for this message has passed.'], 403);
        }

        $conversation = $message->conversation;
        $message->delete();

        if ($conversation) {
            $last = $conversation->messages()->latest('created_at')->latest('id')->first();

            if ($last) {
                $conversation->update(['last_message_at' => $last->created_at]);
            }
        }

        return response()->json(['ok' => true, 'id' => $message->id]);
    }

    // The quoted message must belong to the same conversation; anything else is ignored.
    private function replyTarget(Conversation $conversation, $id): ?int
    {
        if (! $id) {
            return null;
        }

        return $conversation->messages()->whereKey($id)->exists() ? (int) $id : null;
    }
}