<?php

use App\Http\Controllers\Admin\MessageController;
use App\Services\Messaging\MessageService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

// The Message nav item in the admin sidebar shows how many client messages are
// still waiting (read_at = null). Never throws: a DB hiccup just hides the badge.
View::composer('components.admin.sidebar', function ($view) {
    $unread = 0;

    try {
        $unread = app(MessageService::class)->unreadCountForAdmin();
    } catch (\Throwable) {
        $unread = 0;
    }

    $view->with('adminChatUnread', $unread);
});

Route::get('/admin/messages', [MessageController::class, 'index'])->name('admin.messages');

// Live feed for the admin page. MUST stay above the `{conversation}` route so a
// literal "updates" segment is never swallowed by the model binding.
Route::get('/admin/messages/updates', [MessageController::class, 'updates'])->name('admin.messages.updates');

Route::get('/admin/messages/{conversation}', [MessageController::class, 'show'])->name('admin.messages.show');
Route::post('/admin/messages/{conversation}', [MessageController::class, 'store'])->name('admin.messages.send');

// Edit or unsend an admin reply (only messages sent from the admin side are accepted).
Route::patch('/admin/messages/{message}', [MessageController::class, 'update'])->name('admin.messages.update');
Route::delete('/admin/messages/{message}', [MessageController::class, 'destroy'])->name('admin.messages.destroy');