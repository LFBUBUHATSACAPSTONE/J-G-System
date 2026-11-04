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
$updateUrl = Route::has('user.messages.update') ? route('user.messages.update', ['message' => '__MESSAGE__']) : '';
$deleteUrl = Route::has('user.messages.destroy') ? route('user.messages.destroy', ['message' => '__MESSAGE__']) : '';
// Suggested first messages for the empty state: label on the chip => text dropped into the box.
$starters = [
'Ask about packages' => 'Hi! I would like to ask about your packages.',
'Check availability' => 'Hi! Is your team available on my event date?',
'Reschedule my booking' => 'Hi! I would like to reschedule my booking.',
];
@endphp

<div
  class="group/chat fixed right-[calc(2rem+env(safe-area-inset-right,0px))] bottom-[calc(2rem+env(safe-area-inset-bottom,0px))] z-[1040]"
  data-chat
  data-chat-widget
  data-chat-booking-strip
  data-chat-self="client"
  data-chat-other="J&amp;G Audio"
  data-edit-window-minutes="{{ (int) config('admin.messages.edit_window_minutes') }}"
  data-chat-bookings-since="{{ now()->toIso8601String() }}"
  data-chat-messages-since="{{ now()->toIso8601String() }}"
  @if ($authenticated && $updatesUrl) data-chat-updates-url="{{ $updatesUrl }}" @endif
  @if ($authenticated && $readUrl) data-chat-read-url="{{ $readUrl }}" @endif
  @if ($authenticated && $updateUrl) data-chat-update-url="{{ $updateUrl }}" @endif
  @if ($authenticated && $deleteUrl) data-chat-delete-url="{{ $deleteUrl }}" @endif>
  <button type="button" class="relative grid size-14 cursor-pointer place-items-center rounded-full border-0 bg-brand text-[1.75rem] text-on-brand shadow-[0_8px_24px_var(--color-shadow)] hover:bg-brand-hover focus-visible:shadow-[0_0_0_5px_var(--color-brand-text)] focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-surface max-[575.98px]:group-has-[[data-chat-panel]:not([hidden])]/chat:hidden" aria-expanded="false" aria-controls="chatWidgetPanel" data-chat-toggle>
    <x-chat.icon name="chat" />
    <span class="visually-hidden">Message us{{ $unread ? ", {$unread} unread" : '' }}</span>
    @if ($authenticated)
    <span class="absolute -top-0.5 -right-0.5 grid h-[1.375rem] min-w-[1.375rem] place-items-center rounded-full border-2 border-surface bg-danger px-[0.3125rem] text-[0.6875rem] leading-none font-bold text-on-danger" data-chat-badge @if (! $unread) hidden @endif>{{ $unread > 9 ? '9+' : $unread }}</span>
    @endif
  </button>

  <section id="chatWidgetPanel" class="absolute right-0 bottom-18 flex h-[min(34rem,calc(100dvh-10rem))] w-[min(23rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-card bg-surface text-ink shadow-[0_16px_48px_var(--color-shadow)] motion-safe:animate-chat-pop max-[575.98px]:fixed max-[575.98px]:inset-0 max-[575.98px]:h-dvh max-[575.98px]:w-auto max-[575.98px]:rounded-none max-[575.98px]:pt-[env(safe-area-inset-top,0px)]" aria-label="Chat with J&G Audio" hidden data-chat-panel>
    <header class="flex flex-none items-center justify-between gap-2 bg-brand px-4 py-3.5 text-on-brand">
      <span class="grid size-10 flex-none place-items-center rounded-full bg-on-brand/20 text-[0.8125rem] font-bold tracking-[0.02em]" aria-hidden="true">J&amp;G</span>
      <div class="min-w-0 flex-auto">
        <h2 class="m-0 text-base leading-tight font-semibold">J&amp;G Audio Lights and Sounds</h2>
        <p class="mt-0.5 mb-0 text-[0.8125rem] leading-[1.2]">We reply as soon as we can</p>
      </div>
      <button type="button" class="grid size-9 cursor-pointer place-items-center rounded-full border-0 bg-transparent text-[1.25rem] text-inherit pointer-coarse:size-11 hover:bg-on-brand/18 focus-visible:outline-2 focus-visible:outline-surface" aria-label="Close chat" data-chat-close>
        <x-chat.icon name="x" />
      </button>
    </header>

    @if ($authenticated)
    <div class="chat-log flex min-h-0 flex-auto flex-col gap-3 overflow-y-auto overscroll-contain px-4 py-3" role="log" aria-live="polite" tabindex="0" aria-label="Messages" data-chat-log>
      @if (count($items))
      <x-chat.thread :items="$items" self="client" other="J&G Audio" />
      @else
      <div class="chat-empty m-auto max-w-72 text-center text-ink-muted">
        <p>Ask us about packages or your booking. Your booking details appear here once you book.</p>
        <div class="chat-starters mt-4 flex flex-wrap justify-center gap-2" role="group" aria-label="Suggested messages">
          @foreach ($starters as $label => $text)
          <button type="button" class="chat-starters__chip min-h-9 cursor-pointer rounded-full border border-brand-text bg-surface px-3.5 py-1.5 text-sm text-brand-text pointer-coarse:min-h-11 hover:bg-chat-tint focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" data-chat-starter="{{ $text }}">{{ $label }}</button>
          @endforeach
        </div>
      </div>
      @endif
    </div>
    <x-chat.composer :action="Route::has('user.messages.send') ? route('user.messages.send') : '#'"
      :quick="config('admin.messages.client_quick_replies')" :compact="true" />
    @else
    <div class="m-auto grid justify-items-center gap-4 p-6">
      <p class="chat-empty m-auto max-w-72 text-center text-ink-muted">Log in to message us and see your bookings here.</p>
      <button type="button" class="cursor-pointer rounded-full border-0 bg-brand px-6 py-2.5 font-semibold text-on-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login">Log in</button>
    </div>
    @endif
  </section>
</div>