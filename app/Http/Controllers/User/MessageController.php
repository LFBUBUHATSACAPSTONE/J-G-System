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
        ]);

        $attachmentData = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('messages', 'public');
                $attachmentData[] = [
                    'name' => $file->getClientOriginalName(),
                    'url' => Storage::url($path),
                    'kind' => 'file',
                    'size' => $this->messages->formatFileSize($file->getSize()),
                ];
            }
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'client',
            'sender_id' => $user->id,
            'body' => $validated['body'] ?? null,
            'attachments' => $attachmentData ?: null,
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
        $conversation = $this->findConversation($user);
        $unread = $this->messages->unreadCountForUser($user);

        if (! $conversation) {
            return response()->json(['unread' => $unread, 'conversation_id' => null, 'messages' => []]);
        }

        $messages = $conversation->messages()
            ->where('id', '>', $after)
            ->where('sender_type', 'admin')
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message) => $this->messages->formatMessage($message));

        return response()->json([
            'unread' => $unread,
            'conversation_id' => $conversation->id,
            'messages' => $messages,
        ]);
    }

    private function threadFor($user, bool $markRead): array
    {
        $conversation = $this->findConversation($user);

        if (! $conversation) {
            return ['unread' => 0, 'items' => []];
        }

        if ($markRead) {
            $conversation->markReadFor('client');
            $conversation->load('messages');
        }

        return $this->messages->formatConversationForUser($conversation);
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