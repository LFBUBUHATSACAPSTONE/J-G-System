{{-- Admin Messages page (front end only). Variable from the controller:
       $conversations => one per client (shape in docs/admin-messages.md)
     The list, every panel, the booking cards and the quick replies read from $conversations and
     config/admin/messages.php. Nothing is hard-coded, so the stub route can be replaced by a real
     controller without view edits. --}}
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body class="bg-sidebar font-sans">
  <x-admin.sidebar />

  <main class="admin-content">
    <x-admin.page-header />

    <div class="group/layout mt-6 grid grid-cols-[minmax(0,1fr)] items-start gap-6 min-[1280px]:grid-cols-[minmax(17rem,27rem)_minmax(0,1fr)]" data-messages data-view="list"
      data-chat-updates-url="{{ Route::has('admin.messages.updates') ? route('admin.messages.updates') : '' }}"
      data-chat-bookings-since="{{ now()->toIso8601String() }}"
      data-chat-messages-since="{{ now()->toIso8601String() }}">
      <section class="min-w-0 group-data-[view=chat]/layout:hidden min-[1280px]:group-data-[view=chat]/layout:block" aria-labelledby="chatListTitle">
        <h2 id="chatListTitle" class="mt-0 mb-3 text-fluid-2xl font-semibold text-ink">Chat List</h2>

        <div class="mb-3 grid gap-2">
          <div class="relative flex items-center rounded-full border border-field-border bg-surface px-4 focus-within:border-brand-text focus-within:ring-3 focus-within:ring-brand-halo [&_.chat-icon]:text-ink-muted">
            <x-chat.icon name="search" />
            <label class="sr-only" for="chatSearch">Search chats</label>
            <input id="chatSearch" class="min-w-0 flex-1 border-0 bg-transparent px-2 py-3 text-ink outline-none placeholder:text-ink-subtle" type="search" placeholder="Search chats" autocomplete="off" data-chat-search>
          </div>
          <div class="flex gap-2" role="group" aria-label="Filter chats">
            <button type="button" class="cursor-pointer rounded-full border border-field-border bg-surface px-4 py-1 text-ink aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-on-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" aria-pressed="true" data-chat-filter="all">All</button>
            <button type="button" class="cursor-pointer rounded-full border border-field-border bg-surface px-4 py-1 text-ink aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-on-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" aria-pressed="false" data-chat-filter="unread">Unread</button>
          </div>
        </div>

        <p class="sr-only" role="status" data-chat-count></p>

        @if (count($conversations) > 0)
        <ul class="m-0 grid max-h-[calc(100dvh-17rem)] list-none gap-2 overflow-y-auto p-0" data-chat-list>
          @foreach ($conversations as $c)
          @php
          $last = collect($c['items'])->where('type', 'message')->last();
          $first = Str::before($c['client']['name'], ' ');
          $text = $last && ! empty($last['body']) ? $last['body'] : ($last ? 'Sent an attachment' : 'No messages yet');
          $preview = $last && ($last['from'] === 'admin') ? 'You: ' . $text : ($last ? $first . ': ' . $text : $text);
          @endphp
          <li data-chat-item data-name="{{ Str::lower($c['client']['name']) }}" data-unread="{{ $c['unread'] }}">
            <button type="button" class="flex w-full cursor-pointer items-center gap-3 rounded-panel border-2 border-transparent bg-surface p-4 text-left text-ink [&:not([aria-current=true])]:hover:border-field-border aria-current:border-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" data-chat-open="chat-{{ $c['id'] }}" aria-controls="chat-{{ $c['id'] }}" @if ($loop->first) aria-current="true" @endif>
              <span class="chat-avatar inline-grid size-11 flex-none place-items-center rounded-full bg-chat-avatar text-[1.5rem] text-surface"><x-chat.icon name="user" /></span>
              <span class="grid min-w-0 flex-1">
                <span class="chat-item__name font-semibold [[data-chat-item]:not([data-unread='0'])_&]:font-bold">{{ $c['client']['name'] }}</span>
                <span class="overflow-hidden text-fluid-preview text-ellipsis whitespace-nowrap text-ink-muted [[data-chat-item]:not([data-unread='0'])_&]:font-semibold [[data-chat-item]:not([data-unread='0'])_&]:text-ink" data-chat-preview>{{ Str::limit($preview, 34) }}</span>
              </span>
              <span class="grid min-w-0 flex-[0_1_auto] justify-items-end gap-1 text-sm text-ink-muted">
                {{-- Date + time (same shape as the thread stamps); data-at lets messages.js
                     re-render it in the admin's timezone, like every other chat timestamp. --}}
                <time class="text-right" data-chat-when @if ($last) data-at="{{ $last['at']->toIso8601String() }}" @endif>{{ $last ? $last['at']->format('M j, Y g:i A') : '' }}</time>
                <span class="min-w-5.5 rounded-full bg-brand px-2 text-center text-[0.75rem] font-semibold text-on-brand" data-chat-badge @if (! $c['unread']) hidden @endif>{{ $c['unread'] }}<span class="sr-only"> unread</span></span>
              </span>
            </button>
          </li>
          @endforeach
        </ul>
        @else
        <p class="m-0 text-fluid-sm text-ink-muted">No chats yet. Messages from clients appear here.</p>
        @endif
      </section>

      <section class="min-w-0 group-data-[view=list]/layout:hidden min-[1280px]:group-data-[view=list]/layout:block" aria-label="Conversation">
        @foreach ($conversations as $c)
        <div class="chat-panel flex h-[calc(100dvh-11rem)] min-h-96 flex-col overflow-hidden rounded-card bg-surface max-[575.98px]:min-h-0 max-[575.98px]:[&_.chat-log]:px-4 max-[575.98px]:[&_.chat-log]:py-3 max-[575.98px]:[&_.chat-composer]:px-4 max-[575.98px]:[&_.chat-composer]:py-3 max-[575.98px]:[&_.chat-composer__input]:min-h-10.5" id="chat-{{ $c['id'] }}" data-chat data-chat-read-url="{{ Route::has('admin.messages.show') ? route('admin.messages.show', ['conversation' => $c['id']]) : '' }}"
          data-chat-self="admin"
          data-chat-other="{{ $c['client']['name'] }}"
          data-edit-window-minutes="{{ (int) config('admin.messages.edit_window_minutes') }}"
          data-chat-update-url="{{ Route::has('admin.messages.update') ? route('admin.messages.update', ['message' => '__MESSAGE__']) : '' }}"
          data-chat-delete-url="{{ Route::has('admin.messages.destroy') ? route('admin.messages.destroy', ['message' => '__MESSAGE__']) : '' }}"
          @if (! $loop->first) hidden @endif>
          <header class="mx-4 flex flex-none items-center gap-3 border-b border-panel py-3 min-[576px]:mx-6 min-[576px]:py-4">
            <button type="button" class="grid size-10 flex-none cursor-pointer place-items-center rounded-full border-0 bg-panel text-ink pointer-coarse:size-11 min-[1280px]:hidden focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" aria-label="Back to chat list" data-chat-back>
              <x-chat.icon name="chevron-left" />
            </button>
            <span class="chat-avatar inline-grid size-11 flex-none place-items-center rounded-full bg-chat-avatar text-[1.5rem] text-surface"><x-chat.icon name="user" /></span>
            <h3 class="m-0 min-w-0 flex-[1_1_auto] overflow-hidden text-fluid-base font-semibold text-ellipsis whitespace-nowrap">{{ $c['client']['name'] }}</h3>
            @if (! empty($c['client']['phone']))
            <a class="inline-flex flex-none items-center gap-2 whitespace-nowrap text-ink no-underline [&_.chat-icon]:text-fluid-xl [&_.chat-icon]:text-brand-text [&:hover_span]:underline max-[575.98px]:[&_span]:sr-only focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" href="tel:{{ preg_replace('/[^+\d]/', '', $c['client']['phone']) }}">
              <x-chat.icon name="phone" />
              <span>{{ $c['client']['phone'] }}</span>
            </a>
            @endif
          </header>

          <div class="chat-log flex min-h-0 flex-auto flex-col gap-3 overflow-y-auto overscroll-contain px-5 py-4" role="log" aria-live="polite" tabindex="0" aria-label="Messages with {{ $c['client']['name'] }}" data-chat-log>
            <x-chat.thread :items="$c['items']" self="admin" :other="$c['client']['name']" :admin="true" />
          </div>

          <x-chat.composer
            :action="Route::has('admin.messages.send') ? route('admin.messages.send', ['conversation' => $c['id']]) : '#'"
            :quick="config('admin.messages.quick_replies')" />
        </div>
        @endforeach

        @if (count($conversations) === 0)
        <p class="m-0 text-fluid-sm text-ink-muted">Select a chat to read messages.</p>
        @endif
      </section>
    </div>
  </main>
</body>

</html>