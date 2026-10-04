{{-- Message box shared by both sides. Posts to a named route (`action`) through chat.js and
     expects 200 {ok:true} or 422 {message, errors}. Limits come from config/admin-messages.php.
     :quick="[...]" adds quick-reply chips (admin only). --}}
     
@props(['action', 'quick' => []])

@php $att = config('admin-messages.attachments'); @endphp

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
  <div class="chat-quick" role="group" aria-label="Quick replies">
    @foreach ($quick as $text)
    <button type="button" class="chat-quick__chip" data-chat-quick="{{ $text }}">{{ Str::limit($text, 38) }}</button>
    @endforeach
  </div>
  @endif

  <ul class="chat-composer__files" data-chat-files aria-label="Attached files" hidden></ul>
  <p class="chat-composer__error" role="alert" data-chat-error hidden></p>

  <div class="chat-composer__row">
    <label class="chat-composer__attach">
      <x-chat.icon name="paperclip" />
      <span class="visually-hidden">Attach files (JPG, PNG, WEBP or PDF, up to {{ $att['max_kb'] / 1024 }} MB each)</span>
      <input type="file" class="visually-hidden" multiple accept="{{ $att['accept'] }}" data-chat-picker>
    </label>
    <textarea class="chat-composer__input" name="body" rows="1" maxlength="{{ config('admin-messages.max_length') }}" placeholder="Send message…" aria-label="Message" data-chat-input></textarea>
    <button type="submit" class="chat-composer__send" aria-label="Send message">
      <x-chat.icon name="send" />
    </button>
  </div>
</form>