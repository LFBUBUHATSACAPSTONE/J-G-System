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
        $conversations = Conversation::with(['user', 'booking.eventDetails', 'booking.schedule', 'booking.payment', 'booking.package', 'latestMessage'])
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn (Conversation $conversation) => $this->messages->formatConversationForAdmin($conversation));

        return view('admin.messages', ['conversations' => $conversations]);
    }

    public function show(int $id): JsonResponse
    {
        $conversation = Conversation::with(['user', 'booking.eventDetails', 'booking.schedule', 'booking.payment', 'booking.package', 'messages'])
            ->findOrFail($id);

        $conversation->messages->each(function ($message) {
            $message->markAsRead();
        });
        $conversation->markAsRead();

        return response()->json([
            'conversation' => $this->messages->formatConversationForAdmin($conversation, true),
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
            'sender_type' => 'admin',
            'sender_id' => auth()->id(),
            'body' => $validated['body'] ?? null,
            'attachments' => $attachmentData ?: null,
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
}