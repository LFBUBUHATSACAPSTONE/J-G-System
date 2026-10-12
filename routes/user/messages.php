<?php

use App\Http\Controllers\User\MessageController;
use App\Models\Conversation;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

// Landing page chat widget: give the widget this client's real thread when they
// are logged in, an empty thread for guests. The query is guarded so a missing
// table or a DB hiccup never breaks the public landing page.
View::composer('user.landing', function ($view) {
    $conversation = ['unread' => 0, 'items' => []];

    try {
        $user = auth()->user();

        if ($user) {
            $thread = Conversation::with([
                'booking.eventDetails', 'booking.schedule', 'booking.payment',
                'booking.package', 'messages',
            ])
                ->where('user_id', $user->id)
                ->latest('last_message_at')
                ->first();

            if ($thread) {
                $conversation = app(MessageService::class)->formatConversationForUser($thread);
            }
        }
    } catch (\Throwable) {
        $conversation = ['unread' => 0, 'items' => []];
    }

    $view->with('chatConversation', $conversation);
});

// Send message from user (landing widget and account page)
Route::post('/messages', [MessageController::class, 'store'])->name('user.messages.send');