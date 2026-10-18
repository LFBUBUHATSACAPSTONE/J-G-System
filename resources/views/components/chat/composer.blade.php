{{-- Message box shared by both sides. Posts to a named route (`action`) through chat.js and
     expects 200 {ok:true} or 422 {message, errors}. Limits come from config/admin/messages.php.
     :quick="[...]" adds quick-reply chips (admin and client). --}}
@props(['action', 'quick' => []])

@php
$att = config('admin.messages.attachments');
$hintId = 'chat-hint-' . uniqid(); // one per composer (the admin page has several)
@endphp

<form
  class="chat-composer"
  method="POST"
  action="{{ $action }}"
  enctype="multipart/form-data"
  novalidate
  data-chat-form
  data-max-files="{{ $att['max_files'] }}"
  data-max-bytes="{{ $att['max_kb'] * 1024 }}"
  data-accept="{{ $att['accept'] }}">
  @csrf

  @if (count($quick))
  {{-- Quick replies: the "⚡" button in the row below opens this list with the full texts. --}}
  <div class="chat-quickmenu" data-chat-quick-menu hidden>
    <p class="chat-quickmenu__hint">Pick a reply to fill the message box. Nothing is sent until you press Send.</p>
    <div class="chat-quickmenu__list" role="menu" aria-label="Quick replies">
      @foreach ($quick as $text)
      <button type="button" role="menuitem" class="chat-quickmenu__item" data-chat-quick="{{ $text }}">{{ $text }}</button>
      @endforeach
    </div>
  </div>
  @endif

  <div class="chat-composer__reply" data-chat-reply hidden>
    <div class="chat-composer__reply-body">
      <span class="chat-composer__reply-label">Replying to <span data-chat-reply-who>message</span></span>
      <span class="chat-composer__reply-text" data-chat-reply-text></span>
    </div>
    <button type="button" class="chat-composer__reply-cancel" aria-label="Cancel reply" data-chat-reply-cancel>
      <x-chat.icon name="x" />
    </button>
  </div>

  <ul class="chat-composer__files" data-chat-files aria-label="Attached files" hidden></ul>
  <p class="chat-composer__error" role="alert" data-chat-error hidden></p>

  <div class="chat-composer__row">
    <label class="chat-composer__attach">
      <x-chat.icon name="paperclip" />
      <span class="visually-hidden">Attach files (JPG, PNG, WEBP or PDF, up to {{ $att['max_kb'] / 1024 }} MB each)</span>
      <input type="file" class="visually-hidden" multiple accept="{{ $att['accept'] }}" data-chat-picker>
    </label>
    @if (count($quick))
    <button type="button" class="chat-composer__quick" data-chat-quick-toggle aria-haspopup="true" aria-expanded="false" aria-label="Quick replies">
      <x-chat.icon name="zap" />
    </button>
    @endif
    <textarea class="chat-composer__input" name="body" rows="1" maxlength="{{ config('admin.messages.max_length') }}" placeholder="Send message…" aria-label="Message" aria-describedby="{{ $hintId }}" data-chat-input></textarea>
    <button type="submit" class="chat-composer__send" aria-label="Send message">
      <x-chat.icon name="send" />
    </button>
  </div>
  {{-- Keyboard hint: mouse-and-keyboard screens only, shown while the box has focus (CSS). --}}
  <p class="chat-composer__hint" id="{{ $hintId }}"><kbd>Enter</kbd> to send · <kbd>Shift</kbd> + <kbd>Enter</kbd> for a new line</p>
</form>