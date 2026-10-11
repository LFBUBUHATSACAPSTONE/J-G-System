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
@endphp

<x-account.layout>
  <section class="account-chat" data-account-chat aria-labelledby="account-chat-title">
    <header class="account-chat__head">
      <span class="chat-avatar" aria-hidden="true"><x-chat.icon name="chat" /></span>
      <div class="account-chat__heading">
        <h2 class="account-chat__name" id="account-chat-title">J&amp;G Audio Lights and Sounds</h2>
        <p class="account-chat__sub">Ask about packages or your bookings</p>
      </div>
      @if ($unread)
      <span class="account-badge account-badge--brand">{{ $unread }} new<span class="visually-hidden"> unread {{ Str::plural('message', $unread) }}</span></span>
      @endif
    </header>

    <div class="chat-log" role="log" aria-live="polite" tabindex="0" aria-label="Messages with J&G Audio" data-chat-log>
      @if (count($items))
      <x-chat.thread :items="$items" self="client" other="J&G Audio" />
      @else
      <p class="chat-empty">Ask us about packages or your booking. Your booking details appear here once you book.</p>
      @endif
    </div>

    <x-chat.composer :action="Route::has('user.messages.send') ? route('user.messages.send') : '#'" />
  </section>
</x-account.layout>