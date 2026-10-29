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
    class="flex h-[calc(100dvh-11rem)] min-h-96 max-w-[60rem] flex-col overflow-hidden rounded-card bg-surface text-ink"
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
    <header class="mx-4 flex flex-none items-center gap-3 border-b border-panel py-3 min-[576px]:mx-6 min-[576px]:py-4">
      <span class="chat-avatar inline-grid size-11 flex-none place-items-center rounded-full bg-chat-avatar text-[1.5rem] text-surface" aria-hidden="true"><x-chat.icon name="chat" /></span>
      <div class="min-w-0 flex-auto">
        <h2 class="m-0 truncate text-fluid-label font-semibold" id="account-chat-title">J&amp;G Audio Lights and Sounds</h2>
        <p class="m-0 truncate text-sm text-ink-muted">Ask about packages or your bookings</p>
      </div>
      <x-account.badge tone="brand" data-chat-badge :hidden="! $unread">{{ $unread > 9 ? '9+' : $unread }} new<span class="visually-hidden"> unread {{ Str::plural('message', $unread) }}</span></x-account.badge>
    </header>

    <div class="chat-log flex min-h-0 flex-auto flex-col gap-3 overflow-y-auto overscroll-contain px-5 py-4 focus-visible:-outline-offset-2 focus-visible:outline-2 focus-visible:outline-brand-text" role="log" aria-live="polite" tabindex="0" aria-label="Messages with J&G Audio" data-chat-log>
      @if (count($items))
      <x-chat.thread :items="$items" self="client" other="J&G Audio" />
      @else
      <p class="chat-empty m-auto max-w-72 text-center text-ink-muted">Ask us about packages or your booking. Your booking details appear here once you book.</p>
      @endif
    </div>

    <x-chat.composer :action="Route::has('user.messages.send') ? route('user.messages.send') : '#'"
      :quick="config('admin.messages.client_quick_replies')" />
  </section>
</x-account.layout>