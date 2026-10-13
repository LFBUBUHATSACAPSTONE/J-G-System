<?php

use App\Http\Controllers\Admin\MessageController;
use App\Services\MessageService;
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
Route::get('/admin/messages/{conversation}', [MessageController::class, 'show'])->name('admin.messages.show');
Route::post('/admin/messages/{conversation}', [MessageController::class, 'store'])->name('admin.messages.send');