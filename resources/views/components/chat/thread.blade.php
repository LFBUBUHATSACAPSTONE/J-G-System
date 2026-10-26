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
// Utility strings, whole, so Tailwind can find them. The same strings live in resources/js/chat/chat.js (CX):
// keep the two in step.
$dayClass = 'chat-day my-1 self-center rounded-full bg-panel px-3 py-0.5 text-[0.8125rem] text-ink-muted';
$msgClasses = ['out' => 'chat-msg chat-msg--out group/msg flex max-w-[min(32rem,85%)] flex-col items-end gap-0.5 self-end data-[group=last]:-mt-2 data-[group=middle]:-mt-2 [@media(hover:none)]:max-w-[min(32rem,90%)]', 'in' => 'chat-msg chat-msg--in group/msg flex max-w-[min(32rem,85%)] flex-col items-start gap-0.5 self-start data-[group=last]:-mt-2 data-[group=middle]:-mt-2 [@media(hover:none)]:max-w-[min(32rem,90%)]'];
$bubbleClasses = ['out' => 'chat-msg__bubble group/bubble relative flex flex-col gap-2 rounded-panel px-[0.9375rem] py-2.5 wrap-anywhere rounded-br-md bg-brand text-surface group-data-[group=last]/msg:rounded-tr-md group-data-[group=middle]/msg:rounded-tr-md group-[.is-sending]/msg:opacity-70 group-[.is-failed]/msg:outline-1 group-[.is-failed]/msg:-outline-offset-1 group-[.is-failed]/msg:outline-danger group-[.is-menu-open]/msg:shadow-[0_0_0_3px_var(--color-brand-halo)] group-[.is-editing]/msg:shadow-[0_0_0_3px_var(--color-brand-halo)] group-[.is-flash]/msg:motion-safe:animate-chat-flash group-[.is-flash]/msg:motion-reduce:shadow-[0_0_0_3px_var(--color-brand-halo)]', 'in' => 'chat-msg__bubble group/bubble relative flex flex-col gap-2 rounded-panel px-[0.9375rem] py-2.5 wrap-anywhere rounded-bl-md bg-panel text-ink group-data-[group=last]/msg:rounded-tl-md group-data-[group=middle]/msg:rounded-tl-md group-[.is-sending]/msg:opacity-70 group-[.is-failed]/msg:outline-1 group-[.is-failed]/msg:-outline-offset-1 group-[.is-failed]/msg:outline-danger group-[.is-menu-open]/msg:shadow-[0_0_0_3px_var(--color-brand-halo)] group-[.is-editing]/msg:shadow-[0_0_0_3px_var(--color-brand-halo)] group-[.is-flash]/msg:motion-safe:animate-chat-flash group-[.is-flash]/msg:motion-reduce:shadow-[0_0_0_3px_var(--color-brand-halo)]'];
$moreClasses = ['out' => 'chat-msg__more pointer-events-auto absolute top-1/2 right-[calc(100%_+_0.5rem)] grid size-8 -translate-y-1/2 cursor-pointer place-items-center rounded-full border-0 bg-transparent p-0 text-[1.125rem] text-ink-muted opacity-0 transition-opacity duration-[120ms] before:absolute before:inset-y-0 before:left-full before:w-2 hover:bg-panel hover:text-ink focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand-text group-hover/bubble:opacity-100 group-focus-within/bubble:opacity-100 group-[.is-menu-open]/msg:opacity-100 [@media(hover:none)]:pointer-events-none', 'in' => 'chat-msg__more pointer-events-auto absolute top-1/2 left-[calc(100%_+_0.5rem)] grid size-8 -translate-y-1/2 cursor-pointer place-items-center rounded-full border-0 bg-transparent p-0 text-[1.125rem] text-ink-muted opacity-0 transition-opacity duration-[120ms] before:absolute before:inset-y-0 before:right-full before:w-2 hover:bg-panel hover:text-ink focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand-text group-hover/bubble:opacity-100 group-focus-within/bubble:opacity-100 group-[.is-menu-open]/msg:opacity-100 [@media(hover:none)]:pointer-events-none'];
$menuClasses = ['out' => 'chat-msg__menu pointer-events-auto absolute top-[calc(100%_+_0.25rem)] right-0 z-[5] min-w-40 rounded-field border border-panel bg-surface p-1 text-left text-ink shadow-[0_6px_24px_oklch(0%_0_0/0.18)] [&.is-up]:top-auto [&.is-up]:bottom-[calc(100%_+_0.25rem)]', 'in' => 'chat-msg__menu pointer-events-auto absolute top-[calc(100%_+_0.25rem)] left-0 z-[5] min-w-40 rounded-field border border-panel bg-surface p-1 text-left text-ink shadow-[0_6px_24px_oklch(0%_0_0/0.18)] [&.is-up]:top-auto [&.is-up]:bottom-[calc(100%_+_0.25rem)]'];
$actionsClass = 'chat-msg__actions pointer-events-none absolute inset-0';
$actionClass = 'chat-msg__action block min-h-11 w-full cursor-pointer rounded-lg border-0 bg-transparent px-3.5 py-2 text-left text-[0.9375rem] text-ink enabled:hover:bg-panel focus-visible:bg-panel focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand-text disabled:cursor-not-allowed disabled:text-ink-muted data-[chat-action=unsend]:text-danger-text';
$quoteClass = 'chat-msg__quote flex max-w-full flex-col rounded-sm border-l-[3px] border-brand-text bg-panel px-2.5 py-1 text-[0.8125rem] text-ink-muted data-[reply-id]:not-[.is-gone]:cursor-pointer data-[reply-id]:not-[.is-gone]:hover:bg-chat-tint data-[reply-id]:focus-visible:outline-2 data-[reply-id]:focus-visible:outline-offset-2 data-[reply-id]:focus-visible:outline-brand-text [&.is-gone]:italic';
$quoteNameClass = 'chat-msg__quote-name truncate font-semibold';
$quoteTextClass = 'chat-msg__quote-text truncate';
$textClass = 'chat-msg__text m-0 whitespace-pre-wrap wrap-anywhere';
$metaClass = 'chat-msg__meta flex max-w-full items-center gap-2 [.chat-msg[data-group=first]:not(.is-sending):not(.is-failed)_&:not(:has([data-edited]))]:hidden [.chat-msg[data-group=middle]:not(.is-sending):not(.is-failed)_&:not(:has([data-edited]))]:hidden';
$timeClass = 'chat-msg__time px-1 text-[0.75rem] text-ink-muted [.chat-msg[data-group=first]:not(.is-sending)_&]:hidden [.chat-msg[data-group=middle]:not(.is-sending)_&]:hidden group-[.is-failed]/msg:hidden';
$editedClass = 'chat-msg__edited text-[0.75rem] italic text-ink-muted';
$attachFileClass = 'chat-attach flex items-center gap-2 rounded-field bg-surface px-3 py-2 text-sm text-ink no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text [&_.chat-icon]:text-[1.5rem] [&_.chat-icon]:text-danger';
$attachImageClass = 'chat-attach chat-attach--image flex items-center gap-2 overflow-hidden rounded-field bg-transparent p-0 text-sm text-ink no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text';
$attachImgClass = 'block max-h-56 max-w-full rounded-field';
$attachNameClass = 'chat-attach__name max-w-48 truncate';
$attachSizeClass = 'chat-attach__size text-[0.75rem] text-ink-muted';
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
<p class="{{ $dayClass }}" data-day="{{ $at->format('Y-m-d') }}" data-at="{{ $at->toIso8601String() }}">{{ $at->isToday() ? 'Today' : ($at->isYesterday() ? 'Yesterday' : $at->format('M j, Y')) }}</p>
@php $lastDay = $at; @endphp
@endif

@if ($item['type'] === 'booking')
<x-chat.booking-card :booking="$item['booking']" :admin="$admin" />
@else
@php
$out = $item['from'] === $self;
$dir = $out ? 'out' : 'in';
$id = $item['id'] ?? null;
$reply = $item['reply_to'] ?? null;
$editable = $out && ($item['editable'] ?? true);
@endphp
<div class="{{ $msgClasses[$dir] }}" @if ($id) data-message-id="{{ $id }}" @if ($out) data-own="1" @if (! empty($item['edit_expires_at'])) data-editable-until="{{ $item['edit_expires_at'] }}" @endif @endif @endif>
  @if ($reply)
  @php
  // "You replied to Mark" / "Mark replied to you": who wrote this message, and whose message it quotes.
  $quotedMine = $reply['from'] === $self;
  $quoteLabel = $out
  ? ($quotedMine ? 'You replied to yourself' : 'You replied to ' . $other)
  : ($quotedMine ? $other . ' replied to you' : $other . ' replied to themselves');
  @endphp
  {{-- Clicking (or Enter) scrolls to the original message; chat.js reads data-reply-id. --}}
  <div class="{{ $quoteClass }}" role="button" tabindex="0" title="Go to the original message" @if (! empty($reply['id'])) data-reply-id="{{ $reply['id'] }}" @endif>
    <span class="{{ $quoteNameClass }}">{{ $quoteLabel }}</span>
    @if (! empty($reply['body']))
    <span class="{{ $quoteTextClass }}">{{ $reply['body'] }}</span>
    @endif
  </div>
  @endif
  <div class="{{ $bubbleClasses[$dir] }}">
    <span class="visually-hidden">{{ $out ? 'You' : $other }}: </span>
    @if (! empty($item['body']))
    <p class="{{ $textClass }}">{{ $item['body'] }}</p>
    @endif
    @foreach ($item['attachments'] ?? [] as $file)
    @php
    // Trust the stored kind, but also fall back to the file name so image attachments
    // saved before this flag existed still preview inline (no data migration needed).
    $isImage = ($file['kind'] ?? 'file') === 'image'
    || preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) ($file['name'] ?? '')) === 1;
    @endphp
    @if ($isImage)
    <a class="{{ $attachImageClass }}" href="{{ $file['url'] }}" target="_blank" rel="noopener"><img class="{{ $attachImgClass }}" src="{{ $file['url'] }}" alt="{{ $file['name'] }}" loading="lazy"></a>
    @else
    <a class="{{ $attachFileClass }}" href="{{ $file['url'] ?? '#' }}" target="_blank" rel="noopener">
      <x-chat.icon name="file" />
      <span class="{{ $attachNameClass }}">{{ $file['name'] }}</span>
      <span class="{{ $attachSizeClass }}">{{ $file['size'] ?? '' }}</span>
    </a>
    @endif
    @endforeach
    @if ($id)
    <div class="{{ $actionsClass }}">
      <button type="button" class="{{ $moreClasses[$dir] }}" data-chat-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="Message options"><x-chat.icon name="more" /></button>
      <div class="{{ $menuClasses[$dir] }}" role="menu" aria-label="Message options" hidden>
        <button type="button" role="menuitem" class="{{ $actionClass }}" data-chat-action="reply" data-chat-action-id="{{ $id }}">Reply<span class="visually-hidden"> to this message</span></button>
        @if ($editable)
        <button type="button" role="menuitem" class="{{ $actionClass }}" data-chat-action="edit" data-chat-action-id="{{ $id }}" title="Edit{{ $editHint }}">Edit<span class="visually-hidden"> this message</span></button>
        @endif
        @if ($out)
        <button type="button" role="menuitem" class="{{ $actionClass }}" data-chat-action="unsend" data-chat-action-id="{{ $id }}" title="Unsend">Unsend<span class="visually-hidden"> this message</span></button>
        @endif
      </div>
    </div>
    @endif
  </div>
  <div class="{{ $metaClass }}">
    {{-- Clock time only: the Today / Yesterday / date divider above carries the day. The full date
         and time stays in the title (hover). chat.js shows the stamp once per run of messages. --}}
    <time class="{{ $timeClass }}" datetime="{{ $at->toIso8601String() }}" title="{{ $at->format('M j, Y g:i A') }}">{{ $at->format('g:i A') }}</time>
    @if (! empty($item['edited']))
    <span class="{{ $editedClass }}" data-edited>edited</span>
    @endif
  </div>
</div>
@endif
@endforeach