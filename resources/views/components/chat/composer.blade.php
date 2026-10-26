{{-- Message box shared by both sides. Posts to a named route (`action`) through chat.js and
     expects 200 {ok:true} or 422 {message, errors}. Limits come from config/admin/messages.php.
     :quick="[...]" adds quick-reply chips (admin and client). --}}
@props(['action', 'quick' => [], 'compact' => false])

@php
$att = config('admin.messages.attachments');
$tools = count($quick) ? 2 : 1; // buttons in the group on the left (attach, optional quick replies)
// Utility strings, whole, so Tailwind can find them. `compact` = the floating widget: tighter padding, and on phones
// the panel is full screen, so the bottom padding clears the home indicator.
$formClass = $compact ? 'chat-composer group/composer relative flex-none border-t border-panel px-4 pt-2.5 pb-3.5 max-[575.98px]:pb-[calc(0.875rem_+_env(safe-area-inset-bottom,0px))] [--chat-btn:2.625rem] pointer-coarse:[--chat-btn:2.75rem] [--chat-slide:0.28s]' : 'chat-composer group/composer relative flex-none border-t border-panel px-5 pt-3 pb-4 [--chat-btn:2.625rem] pointer-coarse:[--chat-btn:2.75rem] [--chat-slide:0.28s]';
$rowClass = 'chat-composer__row flex items-end gap-2';
$inputClass = 'chat-composer__input max-h-[min(8rem,28dvh)] min-w-0 flex-auto resize-none rounded-field border border-field-border bg-surface px-[0.9375rem] py-2.5 leading-[1.4] text-ink [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden placeholder:text-ink-subtle placeholder-shown:overflow-hidden placeholder-shown:text-ellipsis placeholder-shown:whitespace-nowrap pointer-coarse:min-h-11 pointer-coarse:text-[1rem] focus:shadow-[0_0_0_3px_var(--color-brand-halo)] focus:outline-2 focus:outline-offset-2 focus:outline-transparent';
$toolsClass = 'chat-composer__tools flex flex-none gap-2 [--chat-tools-w:calc(var(--chat-btn)_*_var(--chat-tools,2)_+_0.5rem_*_(var(--chat-tools,2)_-_1))] max-[575.98px]:w-(--chat-tools-w) max-[575.98px]:[transition-property:width,margin,opacity,translate,visibility] max-[575.98px]:[transition-duration:var(--chat-slide),var(--chat-slide),var(--chat-slide),var(--chat-slide),0s] max-[575.98px]:[transition-timing-function:ease,ease,ease,ease,linear] max-[575.98px]:motion-reduce:[transition-property:none] max-[575.98px]:group-[.is-compact]/composer:w-0 max-[575.98px]:group-[.is-compact]/composer:-mr-2 max-[575.98px]:group-[.is-compact]/composer:opacity-0 max-[575.98px]:group-[.is-compact]/composer:[visibility:hidden] max-[575.98px]:group-[.is-compact]/composer:-translate-x-(--chat-tools-w) max-[575.98px]:group-[.is-compact]/composer:[transition-delay:0s,0s,0s,0s,var(--chat-slide)]';
$expandClass = 'chat-composer__expand hidden flex-none place-items-center rounded-full border-0 bg-transparent p-0 text-[1.375rem] text-brand-text cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text max-[575.98px]:inline-grid max-[575.98px]:h-(--chat-btn) max-[575.98px]:w-0 max-[575.98px]:-mr-2 max-[575.98px]:opacity-0 max-[575.98px]:[visibility:hidden] max-[575.98px]:-translate-x-(--chat-btn) max-[575.98px]:[transition-property:width,margin,opacity,translate,visibility] max-[575.98px]:[transition-duration:var(--chat-slide),var(--chat-slide),var(--chat-slide),var(--chat-slide),0s] max-[575.98px]:[transition-timing-function:ease,ease,ease,ease,linear] max-[575.98px]:motion-reduce:[transition-property:none] max-[575.98px]:[transition-delay:0s,0s,0s,0s,var(--chat-slide)] max-[575.98px]:group-[.is-compact]/composer:w-(--chat-btn) max-[575.98px]:group-[.is-compact]/composer:mr-0 max-[575.98px]:group-[.is-compact]/composer:opacity-100 max-[575.98px]:group-[.is-compact]/composer:[visibility:visible] max-[575.98px]:group-[.is-compact]/composer:translate-x-0 max-[575.98px]:group-[.is-compact]/composer:[transition-delay:0s]';
$attachClass = 'chat-composer__attach relative inline-grid size-(--chat-btn) flex-none cursor-pointer place-items-center rounded-full border-0 text-[1.375rem] bg-panel text-ink focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text group-[.is-editing]/composer:pointer-events-none group-[.is-editing]/composer:opacity-40';
$quickClass = 'chat-composer__quick relative inline-grid size-(--chat-btn) flex-none cursor-pointer place-items-center rounded-full border-0 text-[1.375rem] bg-panel text-ink aria-expanded:bg-chat-tint aria-expanded:text-brand-text focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text';
$sendClass = 'chat-composer__send relative inline-grid size-(--chat-btn) flex-none cursor-pointer place-items-center rounded-full border-0 text-[1.375rem] bg-brand text-surface hover:bg-brand-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text';
$filesClass = 'chat-composer__files mt-0 mb-2 flex flex-wrap gap-1.5 p-0';
$errorClass = 'chat-composer__error mt-0 mb-2 text-sm text-danger-text';
$replyClass = 'chat-composer__reply mt-0 mb-2 flex items-center justify-between gap-2 rounded-sm border-l-[3px] border-brand-text bg-panel px-2.5 py-1.5 pointer-coarse:py-0.5';
$replyBodyClass = 'chat-composer__reply-body flex min-w-0 flex-col';
$replyLabelClass = 'chat-composer__reply-label text-[0.75rem] font-semibold text-brand-text';
$replyTextClass = 'chat-composer__reply-text truncate text-[0.8125rem] text-ink-muted';
$replyTextWrapClass = 'chat-composer__reply-text text-[0.8125rem] whitespace-normal text-ink-muted';
$replyCancelClass = 'chat-composer__reply-cancel inline-grid size-6 flex-none place-items-center rounded-full border-0 bg-transparent p-0 text-[1rem] text-ink cursor-pointer pointer-coarse:size-11 focus-visible:outline-2 focus-visible:outline-brand-text';
$quickmenuClass = 'chat-quickmenu absolute right-5 bottom-[calc(100%_+_0.5rem)] left-5 z-[6] flex max-h-[min(18rem,45dvh)] flex-col rounded-field border border-panel bg-surface text-ink shadow-[0_6px_24px_oklch(0%_0_0/0.18)]';
$quickHintClass = 'chat-quickmenu__hint m-0 flex-none border-b border-panel px-3.5 py-2 text-[0.8125rem] text-ink-muted';
$quickListClass = 'chat-quickmenu__list overflow-y-auto overscroll-contain p-1 [scrollbar-color:var(--color-field-border)_transparent] [scrollbar-width:thin]';
$quickItemClass = 'chat-quickmenu__item block min-h-11 w-full cursor-pointer rounded-lg border-0 bg-transparent px-3 py-2.5 text-left text-[0.9375rem] leading-[1.35] text-ink wrap-anywhere hover:bg-panel focus-visible:bg-panel focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand-text';
@endphp

<form
  class="{{ $formClass }}"
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
  <div class="{{ $quickmenuClass }}" data-chat-quick-menu hidden>
    <p class="{{ $quickHintClass }}">Pick a reply to fill the message box. Nothing is sent until you press Send.</p>
    <div class="{{ $quickListClass }}" role="menu" aria-label="Quick replies">
      @foreach ($quick as $text)
      <button type="button" role="menuitem" class="{{ $quickItemClass }}" data-chat-quick="{{ $text }}">{{ $text }}</button>
      @endforeach
    </div>
  </div>
  @endif

  <div class="{{ $replyClass }}" data-chat-reply hidden>
    <div class="{{ $replyBodyClass }}">
      <span class="{{ $replyLabelClass }}">Replying to <span data-chat-reply-who>message</span></span>
      <span class="{{ $replyTextClass }}" data-chat-reply-text></span>
    </div>
    <button type="button" class="{{ $replyCancelClass }}" aria-label="Cancel reply" data-chat-reply-cancel>
      <x-chat.icon name="x" />
    </button>
  </div>

  {{-- Edit mode: "Edit" on a message loads its text into the message box below and shows this bar
       (label + how long the sender can still edit). Send saves, the X or Esc cancels. --}}
  <div class="chat-composer__reply--edit {{ $replyClass }}" data-chat-edit-bar hidden>
    <div class="{{ $replyBodyClass }}">
      <span class="{{ $replyLabelClass }}">Editing message</span>
      <span class="{{ $replyTextWrapClass }}" data-chat-edit-hint></span>
    </div>
    <button type="button" class="{{ $replyCancelClass }}" aria-label="Cancel editing" data-chat-edit-cancel>
      <x-chat.icon name="x" />
    </button>
  </div>

  <ul class="{{ $filesClass }}" data-chat-files aria-label="Attached files" hidden></ul>
  <p class="{{ $errorClass }}" role="alert" data-chat-error hidden></p>

  <div class="{{ $rowClass }}">
    {{-- Attach + quick replies move together: on phones the whole group slides left while the box is focused. --}}
    <div class="{{ $toolsClass }}" style="--chat-tools: {{ $tools }}">
      <label class="{{ $attachClass }}">
        <x-chat.icon name="paperclip" />
        <span class="visually-hidden">Attach files (JPG, PNG, WEBP or PDF, up to {{ $att['max_kb'] / 1024 }} MB each)</span>
        <input type="file" class="visually-hidden" multiple accept="{{ $att['accept'] }}" data-chat-picker>
      </label>
      @if (count($quick))
      <button type="button" class="{{ $quickClass }}" data-chat-quick-toggle aria-haspopup="true" aria-expanded="false" aria-label="Quick replies">
        <x-chat.icon name="zap" />
      </button>
      @endif
    </div>
    {{-- Phones: while the box is focused, the attach and quick-reply buttons collapse into this
         chevron (like Messenger); tapping it brings them back. Hidden by CSS on wider screens. --}}
    <button type="button" class="{{ $expandClass }}" data-chat-expand aria-expanded="true" aria-label="Show attach and quick reply buttons">
      <x-chat.icon name="chevron-right" />
    </button>
    <textarea class="{{ $inputClass }}" name="body" rows="1" maxlength="{{ config('admin.messages.max_length') }}" placeholder="Send message…" aria-label="Message" data-chat-input></textarea>
    <button type="submit" class="{{ $sendClass }}" aria-label="Send message">
      <x-chat.icon name="send" />
    </button>
  </div>
</form>