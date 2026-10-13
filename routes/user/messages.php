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
                'booking.package', 'messages.replyTo',
            ])
                ->where('user_id', $user->id)
                ->latest('last_message_at')
                ->latest('id')
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

// The unread bubble on the sign-in chat icon and the account sidebar's Messages
// tab: never queries the DB for guests and never throws for logged-in users.
View::composer(['components.landing.navigation', 'components.account.sidebar'], function ($view) {
    $user = auth()->user();
    $unread = 0;

    if ($user) {
        try {
            $unread = app(MessageService::class)->unreadCountForUser($user);
        } catch (\Throwable) {
            $unread = 0;
        }
    }

    $view->with('chatUnread', $unread);
});

// Send message from user (landing widget and account page).
Route::post('/messages', [MessageController::class, 'store'])->name('user.messages.send');

// Edit or unsend a message the client already sent. Only their own client messages in
// their own thread are accepted (checked in the controller).
Route::patch('/messages/{message}', [MessageController::class, 'update'])->name('user.messages.update');
Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('user.messages.destroy');

// Opening the chat on the user side marks only the admin replies read.
Route::post('/messages/read', [MessageController::class, 'markRead'])->name('user.messages.read');

// Light polling feed for an open chat: unread count + admin replies after `after`.
Route::get('/messages/updates', [MessageController::class, 'updates'])->name('user.messages.updates');