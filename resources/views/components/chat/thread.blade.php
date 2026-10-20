{{-- Message list shared by the admin panel and the user widget.
     $items: ordered list. Each item has `at` (Carbon) and `type`:
       'message' => id, from ('admin'|'client'), body (string|null),
                    attachments [{name,url,kind:'image'|'file',size}],
                    edited (bool), reply_to ({id,from,body}|null)
       'booking' => booking
     $self: which side is looking ('admin' | 'client'); their messages sit right, in purple.
     $other: name read out to screen readers for the other side.
     Edit / unsend buttons are only rendered on the reader's OWN messages; the message id is
     handed to chat.js, which builds the request URLs from the chat root's data attributes.
     Editing is limited to the config window; unsending has no time limit. --}}

@props(['items' => [], 'self' => 'admin', 'other' => 'Client', 'admin' => false])

@php
$lastDay = null;
// EDIT window (config value 0 = no limit). Only the Edit button is gated by it; the
// controllers enforce the same limit server-side. Unsending is never time-limited.
$editWindow = (int) config('admin.messages.edit_window_minutes', 0);
$editHint = $editWindow > 0
? ' (available for ' . $editWindow . ' minute' . ($editWindow === 1 ? '' : 's') . ' after sending)'
: '';
@endphp

@foreach ($items as $item)
@php $at = $item['at']; @endphp

@if ($lastDay === null || ! $at->isSameDay($lastDay))
<p class="chat-day" data-day="{{ $at->format('Y-m-d') }}" data-at="{{ $at->toIso8601String() }}">{{ $at->isToday() ? 'Today' : ($at->isYesterday() ? 'Yesterday' : $at->format('M j, Y')) }}</p>
@php $lastDay = $at; @endphp
@endif

@if ($item['type'] === 'booking')
<x-chat.booking-card :booking="$item['booking']" :admin="$admin" />
@else
@php
$out = $item['from'] === $self;
$id = $item['id'] ?? null;
$reply = $item['reply_to'] ?? null;
$editable = $out && ($item['editable'] ?? true);
@endphp
<div class="chat-msg chat-msg--{{ $out ? 'out' : 'in' }}" @if ($id) data-message-id="{{ $id }}" @if ($out) data-own="1" @if (! empty($item['edit_expires_at'])) data-editable-until="{{ $item['edit_expires_at'] }}" @endif @endif @endif>
  @if ($reply)
  @php
  // "You replied to Mark" / "Mark replied to you": who wrote this message, and whose message it quotes.
  $quotedMine = $reply['from'] === $self;
  $quoteLabel = $out
  ? ($quotedMine ? 'You replied to yourself' : 'You replied to ' . $other)
  : ($quotedMine ? $other . ' replied to you' : $other . ' replied to themselves');
  @endphp
  {{-- Clicking (or Enter) scrolls to the original message; chat.js reads data-reply-id. --}}
  <div class="chat-msg__quote" role="button" tabindex="0" title="Go to the original message" @if (! empty($reply['id'])) data-reply-id="{{ $reply['id'] }}" @endif>
    <span class="chat-msg__quote-name">{{ $quoteLabel }}</span>
    @if (! empty($reply['body']))
    <span class="chat-msg__quote-text">{{ $reply['body'] }}</span>
    @endif
  </div>
  @endif
  <div class="chat-msg__bubble">
    <span class="visually-hidden">{{ $out ? 'You' : $other }}: </span>
    @if (! empty($item['body']))
    <p class="chat-msg__text">{{ $item['body'] }}</p>
    @endif
    @foreach ($item['attachments'] ?? [] as $file)
    @php
    // Trust the stored kind, but also fall back to the file name so image attachments
    // saved before this flag existed still preview inline (no data migration needed).
    $isImage = ($file['kind'] ?? 'file') === 'image'
    || preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) ($file['name'] ?? '')) === 1;
    @endphp
    @if ($isImage)
    <a class="chat-attach chat-attach--image" href="{{ $file['url'] }}" target="_blank" rel="noopener"><img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" loading="lazy"></a>
    @else
    <a class="chat-attach" href="{{ $file['url'] ?? '#' }}" target="_blank" rel="noopener">
      <x-chat.icon name="file" />
      <span class="chat-attach__name">{{ $file['name'] }}</span>
      <span class="chat-attach__size">{{ $file['size'] ?? '' }}</span>
    </a>
    @endif
    @endforeach
    @if ($id)
    <div class="chat-msg__actions">
      <button type="button" class="chat-msg__more" data-chat-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="Message options"><x-chat.icon name="more" /></button>
      <div class="chat-msg__menu" role="menu" aria-label="Message options" hidden>
        <button type="button" role="menuitem" class="chat-msg__action" data-chat-action="reply" data-chat-action-id="{{ $id }}">Reply<span class="visually-hidden"> to this message</span></button>
        @if ($editable)
        <button type="button" role="menuitem" class="chat-msg__action" data-chat-action="edit" data-chat-action-id="{{ $id }}" title="Edit{{ $editHint }}">Edit<span class="visually-hidden"> this message</span></button>
        @endif
        @if ($out)
        <button type="button" role="menuitem" class="chat-msg__action" data-chat-action="unsend" data-chat-action-id="{{ $id }}" title="Unsend">Unsend<span class="visually-hidden"> this message</span></button>
        @endif
      </div>
    </div>
    @endif
  </div>
  <div class="chat-msg__meta">
    {{-- Clock time only: the Today / Yesterday / date divider above carries the day. The full date
         and time stays in the title (hover). chat.js shows the stamp once per run of messages. --}}
    <time class="chat-msg__time" datetime="{{ $at->toIso8601String() }}" title="{{ $at->format('M j, Y g:i A') }}">{{ $at->format('g:i A') }}</time>
    @if (! empty($item['edited']))
    <span class="chat-msg__edited">edited</span>
    @endif
  </div>
</div>
@endif
@endforeach