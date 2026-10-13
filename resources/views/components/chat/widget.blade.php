{{-- User-side floating chat: circle with a chat icon, bottom right, opens a compact panel.
     :conversation => this client's single thread (`items` + optional `unread`), or null
     :authenticated => pass auth()->check(). Guests see a "log in" prompt that opens the existing auth modal.
     Place once per page, before </body>.
     While the page is open widget.js polls `user.messages.updates` for new admin replies and marks
     the thread read through `user.messages.read` the moment the panel opens, so the unread badge on
     the sign-in chat icon reflects reality and stays cleared. --}}

@props(['conversation' => null, 'authenticated' => true])

@php
$items = $conversation['items'] ?? [];
$unread = $conversation['unread'] ?? 0;
$updatesUrl = Route::has('user.messages.updates') ? route('user.messages.updates') : '';
$readUrl = Route::has('user.messages.read') ? route('user.messages.read') : '';
@endphp

<div
  class="chat-widget"
  data-chat
  data-chat-widget
  @if ($authenticated && $updatesUrl) data-chat-updates-url="{{ $updatesUrl }}" @endif
  @if ($authenticated && $readUrl) data-chat-read-url="{{ $readUrl }}" @endif>
  <button type="button" class="chat-widget__fab" aria-expanded="false" aria-controls="chatWidgetPanel" data-chat-toggle>
    <x-chat.icon name="chat" />
    <span class="visually-hidden">Message us{{ $unread ? ", {$unread} unread" : '' }}</span>
    @if ($authenticated)
    <span class="chat-widget__badge" data-chat-badge @if (! $unread) hidden @endif>{{ $unread > 9 ? '9+' : $unread }}</span>
    @endif
  </button>

  <section id="chatWidgetPanel" class="chat-widget__panel" aria-label="Chat with J&G Audio" hidden data-chat-panel>
    <header class="chat-widget__head">
      <h2 class="chat-widget__title">J&amp;G Audio Lights and Sounds</h2>
      <button type="button" class="chat-widget__close" aria-label="Close chat" data-chat-close>
        <x-chat.icon name="x" />
      </button>
    </header>

    @if ($authenticated)
    <div class="chat-log" role="log" aria-live="polite" tabindex="0" aria-label="Messages" data-chat-log>
      @if (count($items))
      <x-chat.thread :items="$items" self="client" other="J&G Audio" />
      @else
      <p class="chat-empty">Ask us about packages or your booking. Your booking details appear here once you book.</p>
      @endif
    </div>
    <x-chat.composer :action="Route::has('user.messages.send') ? route('user.messages.send') : '#'" />
    @else
    <div class="chat-widget__gate">
      <p class="chat-empty">Log in to message us and see your bookings here.</p>
      <button type="button" class="chat-widget__login" data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login">Log in</button>
    </div>
    @endif
  </section>
</div>