{{-- Admin Messages page (front end only). Variable from the controller:
       $conversations => one per client 
     The list, every panel, the booking cards and the quick replies read from $conversations and
     config/admin-messages.php. Nothing is hard-coded, so the stub route can be replaced by a real
     controller without view edits. --}}
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body>
  <x-admin.sidebar />

  <main class="admin-content admin-messages p-4">
    <x-admin.page-header />

    <div class="admin-messages__layout" data-messages data-view="list">
      <section class="admin-messages__side" aria-labelledby="chatListTitle">
        <h2 id="chatListTitle" class="admin-messages__title">Chat List</h2>

        <div class="admin-messages__tools">
          <div class="admin-messages__search">
            <x-chat.icon name="search" />
            <label class="visually-hidden" for="chatSearch">Search chats</label>
            <input id="chatSearch" type="search" placeholder="Search chats" autocomplete="off" data-chat-search>
          </div>
          <div class="admin-messages__tabs" role="group" aria-label="Filter chats">
            <button type="button" class="is-active" aria-pressed="true" data-chat-filter="all">All</button>
            <button type="button" aria-pressed="false" data-chat-filter="unread">Unread</button>
          </div>
        </div>

        <p class="visually-hidden" role="status" data-chat-count></p>

        @if (count($conversations) > 0)
        <ul class="admin-messages__list" data-chat-list>
          @foreach ($conversations as $c)
          @php
          $last = collect($c['items'])->where('type', 'message')->last();
          $first = Str::before($c['client']['name'], ' ');
          $text = ! empty($last['body']) ? $last['body'] : 'Sent an attachment';
          $preview = ($last['from'] === 'admin' ? 'You' : $first) . ': ' . $text;
          @endphp
          <li data-chat-item data-name="{{ Str::lower($c['client']['name']) }}" data-unread="{{ $c['unread'] }}">
            <button type="button" class="chat-item{{ $loop->first ? ' is-active' : '' }}" data-chat-open="chat-{{ $c['id'] }}" aria-controls="chat-{{ $c['id'] }}" @if ($loop->first) aria-current="true" @endif>
              <span class="chat-avatar"><x-chat.icon name="user" /></span>
              <span class="chat-item__body">
                <span class="chat-item__name">{{ $c['client']['name'] }}</span>
                <span class="chat-item__preview" data-chat-preview>{{ Str::limit($preview, 34) }}</span>
              </span>
              <span class="chat-item__meta">
                <time data-chat-when>{{ $last['at']->diffForHumans(null, true, true) }}</time>
                <span class="chat-item__badge" data-chat-badge @if (! $c['unread']) hidden @endif>{{ $c['unread'] }}<span class="visually-hidden"> unread</span></span>
              </span>
            </button>
          </li>
          @endforeach
        </ul>
        @else
        <p class="admin-empty">No chats yet. Messages from clients appear here.</p>
        @endif
      </section>

      <section class="admin-messages__panels" aria-label="Conversation">
        @foreach ($conversations as $c)
        <div class="chat-panel" id="chat-{{ $c['id'] }}" data-chat @if (! $loop->first) hidden @endif>
          <header class="chat-panel__head">
            <button type="button" class="chat-panel__back d-lg-none" aria-label="Back to chat list" data-chat-back>
              <x-chat.icon name="chevron-left" />
            </button>
            <span class="chat-avatar"><x-chat.icon name="user" /></span>
            <h3 class="chat-panel__name">{{ $c['client']['name'] }}</h3>
            @if (! empty($c['client']['phone']))
            <a class="chat-panel__phone" href="tel:{{ preg_replace('/[^+\d]/', '', $c['client']['phone']) }}">
              <x-chat.icon name="phone" />
              <span>{{ $c['client']['phone'] }}</span>
            </a>
            @endif
          </header>

          <div class="chat-log" role="log" aria-live="polite" tabindex="0" aria-label="Messages with {{ $c['client']['name'] }}" data-chat-log>
            <x-chat.thread :items="$c['items']" self="admin" :other="$c['client']['name']" :admin="true" />
          </div>

          <x-chat.composer
            :action="Route::has('admin.messages.send') ? route('admin.messages.send', ['conversation' => $c['id']]) : '#'"
            :quick="config('admin-messages.quick_replies')" />
        </div>
        @endforeach

        @if (count($conversations) === 0)
        <p class="admin-empty">Select a chat to read messages.</p>
        @endif
      </section>
    </div>
  </main>
</body>

</html>