{{-- Message list shared by the admin panel and the user widget.
     $items: ordered list. Each item has `at` (Carbon) and `type`:
       'message' => from ('admin'|'client'), body (string|null), attachments [{name,url,kind:'image'|'file',size}]
       'booking' => booking 
     $self: which side is looking ('admin' | 'client'); their messages sit right, in purple.
     $other: name read out to screen readers for the other side. --}}
     
@props(['items' => [], 'self' => 'admin', 'other' => 'Client', 'admin' => false])

@php $lastDay = null; @endphp

@foreach ($items as $item)
@php $at = $item['at']; @endphp

@if ($lastDay === null || ! $at->isSameDay($lastDay))
<p class="chat-day" data-day="{{ $at->format('Y-m-d') }}">{{ $at->isToday() ? 'Today' : ($at->isYesterday() ? 'Yesterday' : $at->format('M j, Y')) }}</p>
@php $lastDay = $at; @endphp
@endif

@if ($item['type'] === 'booking')
<x-chat.booking-card :booking="$item['booking']" :admin="$admin" />
@else
@php $out = $item['from'] === $self; @endphp
<div class="chat-msg chat-msg--{{ $out ? 'out' : 'in' }}">
  <div class="chat-msg__bubble">
    <span class="visually-hidden">{{ $out ? 'You' : $other }}: </span>
    @if (! empty($item['body']))
    <p class="chat-msg__text">{{ $item['body'] }}</p>
    @endif
    @foreach ($item['attachments'] ?? [] as $file)
    @if (($file['kind'] ?? 'file') === 'image')
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
  <time class="chat-msg__time" datetime="{{ $at->toIso8601String() }}">{{ $at->format('g:i A') }}</time>
</div>
@endif
@endforeach