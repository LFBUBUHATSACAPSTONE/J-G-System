{{-- Server flash as toasts. Controllers keep doing redirect()->with('status', ...) / ->with('error', ...);
     this turns those two keys (config/toast.php 'flash_tones') into hidden markers that toast.js reads on load and
     removes. Used by <x-toast.region />, so a page does not include it by itself.
     Without JavaScript the <noscript> block prints the old plain banner, so the message is never lost. --}}
@php
$flash = collect(config('toast.flash_tones'))
->map(fn ($tone, $key) => ['tone' => $tone, 'message' => session($key)])
->filter(fn ($item) => is_string($item['message']) && trim($item['message']) !== '')
->values();
@endphp
@foreach ($flash as $item)
<div hidden data-toast-flash data-tone="{{ $item['tone'] }}" data-message="{{ $item['message'] }}"></div>
@endforeach
@if ($flash->isNotEmpty())
<noscript>
  <div class="fixed inset-x-0 top-0 z-[1090] flex flex-col gap-2 p-4">
    @foreach ($flash as $item)
    <p class="m-0 rounded-panel border px-4 py-3 font-medium {{ $item['tone'] === 'danger' ? 'border-danger-border bg-danger-soft text-danger-text' : 'border-success-border bg-success-soft text-success-text' }}" role="{{ $item['tone'] === 'danger' ? 'alert' : 'status' }}">{{ $item['message'] }}</p>
    @endforeach
  </div>
</noscript>
@endif