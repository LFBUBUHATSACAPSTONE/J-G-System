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
        $conversation = $this->findConversation($user);

        if ($conversation) {
            $this->markClientRead($conversation);
            $thread = $this->messages->formatConversationForUser($conversation);
        } else {
            $thread = ['unread' => 0, 'items' => []];
        }

        return view('user.account.messages', [
            'conversation' => $thread,
        ]);
    }

    public function indexJson(): JsonResponse
    {
        $user = auth()->user();
        $conversation = $this->findConversation($user);

        if ($conversation) {
            $this->markClientRead($conversation);
            $thread = $this->messages->formatConversationForUser($conversation);
        } else {
            $thread = ['unread' => 0, 'items' => []];
        }

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

    private function findConversation($user, bool $create = false): ?Conversation
    {
        if (! $user) {
            return null;
        }

        $conversation = Conversation::with(['booking.eventDetails', 'booking.schedule', 'booking.payment', 'booking.package', 'messages'])
            ->where('user_id', $user->id)
            ->latest('last_message_at')
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

    private function markClientRead(Conversation $conversation): void
    {
        $conversation->messages->each(function ($message) {
            if ($message->sender_type === 'admin') {
                $message->markAsRead();
            }
        });
        $conversation->markAsRead();
    }
}