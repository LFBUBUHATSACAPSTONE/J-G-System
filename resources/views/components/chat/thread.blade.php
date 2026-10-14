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
  <div class="chat-msg__quote">
    <span class="chat-msg__quote-name">{{ $reply['from'] === $self ? 'You' : $other }}</span>
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
  </div>
  <div class="chat-msg__meta">
    <time class="chat-msg__time" datetime="{{ $at->toIso8601String() }}">{{ $at->format('g:i A') }}</time>
    @if (! empty($item['edited']))
    <span class="chat-msg__edited">edited</span>
    @endif
    @if ($id)
    <span class="chat-msg__actions">
      <button type="button" class="chat-msg__action" data-chat-action="reply" data-chat-action-id="{{ $id }}">Reply<span class="visually-hidden"> to this message</span></button>
      @if ($editable)
      <button type="button" class="chat-msg__action" data-chat-action="edit" data-chat-action-id="{{ $id }}" title="Edit{{ $editHint }}">Edit<span class="visually-hidden"> this message</span></button>
      @endif
      @if ($out)
      <button type="button" class="chat-msg__action" data-chat-action="unsend" data-chat-action-id="{{ $id }}" title="Unsend">Unsend<span class="visually-hidden"> this message</span></button>
      @endif
    </span>
    @endif
  </div>
</div>
@endif
@endforeach
