{{-- Account > Messages. FRONT END ONLY. Variable from the controller:
       $conversation => this client's single thread: ['unread' => int, 'items' => [...]]
     Same shape the landing page chat widget gets (docs/user-account.md -> Messages), so the real
     controller can pass the same data to both. The thread, the composer and the booking cards are
     the shared chat components (components/chat/*) and chat.js, so sending, attachments and limits
     behave exactly like the widget and post to the existing `user.messages.send` route. Nothing
     here is new for the backend. --}}
@php
$items = $conversation['items'] ?? [];
$unread = (int) ($conversation['unread'] ?? 0);
$updatesUrl = Route::has('user.messages.updates') ? route('user.messages.updates') : '';
$readUrl = Route::has('user.messages.read') ? route('user.messages.read') : '';
$updateUrl = Route::has('user.messages.update') ? route('user.messages.update', ['message' => '__MESSAGE__']) : '';
$deleteUrl = Route::has('user.messages.destroy') ? route('user.messages.destroy', ['message' => '__MESSAGE__']) : '';
@endphp

<x-account.layout>
  <section
    class="account-chat"
    data-account-chat
    data-chat-self="client"
    data-chat-other="J&amp;G Audio"
    data-edit-window-minutes="{{ (int) config('admin.messages.edit_window_minutes') }}"
    data-chat-bookings-since="{{ now()->toIso8601String() }}"
    data-chat-messages-since="{{ now()->toIso8601String() }}"
    aria-labelledby="account-chat-title"
    @if ($updatesUrl) data-chat-updates-url="{{ $updatesUrl }}" @endif
    @if ($readUrl) data-chat-read-url="{{ $readUrl }}" @endif
    @if ($updateUrl) data-chat-update-url="{{ $updateUrl }}" @endif
    @if ($deleteUrl) data-chat-delete-url="{{ $deleteUrl }}" @endif>
    <header class="account-chat__head">
      <span class="chat-avatar" aria-hidden="true"><x-chat.icon name="chat" /></span>
      <div class="account-chat__heading">
        <h2 class="account-chat__name" id="account-chat-title">J&amp;G Audio Lights and Sounds</h2>
        <p class="account-chat__sub">Ask about packages or your bookings</p>
      </div>
      <span class="account-badge account-badge--brand" data-chat-badge @if (! $unread) hidden @endif>{{ $unread > 9 ? '9+' : $unread }} new<span class="visually-hidden"> unread {{ Str::plural('message', $unread) }}</span></span>
    </header>

    <div class="chat-log" role="log" aria-live="polite" tabindex="0" aria-label="Messages with J&G Audio" data-chat-log>
      @if (count($items))
      <x-chat.thread :items="$items" self="client" other="J&G Audio" />
      @else
      <p class="chat-empty">Ask us about packages or your booking. Your booking details appear here once you book.</p>
      @endif
    </div>

    <x-chat.composer :action="Route::has('user.messages.send') ? route('user.messages.send') : '#'"
      :quick="config('admin.messages.client_quick_replies')" />
  </section>
</x-account.layout>