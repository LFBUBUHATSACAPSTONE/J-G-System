{{-- Toast region: the empty container at the top of the screen that resources/js/shared/toast.js fills.
     Put ONE <x-toast.region /> in the <body> of every page that shows toasts (right after the sidebar on the admin
     pages). It cannot go in <x-admin-header /> (that is inside <head>) and not inside the sidebar (an offcanvas panel
     has a transform, which would break position: fixed).
     It also prints the server flash (session('status') / session('error')) through <x-toast.flash />.
     Settings come from config/toast.php. z-[1090] sits above the sticky admin menu bar (1030), Bootstrap's offcanvas
     and modals (1045 to 1055) and the custom dropdown menu (1080).
     pointer-events-none on the container: only the toasts themselves catch clicks, so the page under the empty
     strip stays usable.
     feed="admin" | "user" (Phase 4): also hands resources/js/shared/toast-feed.js the existing `updates` URL of that side,
     so a message from the other party shows as a toast on pages that have no chat of their own. Leave it off on
     pages that already show the thread live (admin Messages). A guest gets no feed. --}}
@props(['feed' => null])

@php
$toast = config('toast');
$settings = [
'max' => $toast['max_visible'],
'mergeMs' => $toast['merge_window_ms'],
'durations' => $toast['durations_ms'],
'close' => $toast['labels']['close'],
'merged' => $toast['labels']['merged'],
];

// Phase 4: the existing `updates` endpoint of this side (no new route). Guarded like the chat widget:
// no route or no signed-in client = no feed.
$feedSettings = null;
if ($feed === 'admin' && Route::has('admin.messages.updates')) {
$feedSettings = [
'side' => 'admin',
'url' => route('admin.messages.updates'),
'link' => Route::has('admin.messages') ? route('admin.messages') : '',
];
} elseif ($feed === 'user' && auth('web')->check() && Route::has('user.messages.updates')) {
$feedSettings = [
'side' => 'user',
'url' => route('user.messages.updates'),
'link' => Route::has('user.account.messages') ? route('user.account.messages') : '',
'bookingsLink' => Route::has('user.account.bookings') ? route('user.account.bookings') : '',
];
}
if ($feedSettings) {
$feedSettings += [
'pollMs' => $toast['feed']['poll_ms'],
'maxMs' => $toast['feed']['max_ms'],
'text' => $toast['feed']['text'][$feedSettings['side']],
];
}
@endphp
<div class="pointer-events-none fixed inset-x-0 top-0 z-[1090] flex flex-col items-center gap-3 px-4 pt-[calc(env(safe-area-inset-top,0px)+1rem)] min-[576px]:pt-[calc(env(safe-area-inset-top,0px)+1.5rem)]" role="region" aria-label="{{ $toast['labels']['region'] }}" data-toast-region data-toast-config="{{ json_encode($settings) }}" @if ($feedSettings) data-toast-feed="{{ json_encode($feedSettings) }}" @endif></div>
<x-toast.flash />