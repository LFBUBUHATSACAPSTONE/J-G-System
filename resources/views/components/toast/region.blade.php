{{-- Toast region: the empty container at the top of the screen that resources/js/shared/toast.js fills.
     Put ONE <x-toast.region /> in the <body> of every page that shows toasts (right after the sidebar on the admin
     pages). It cannot go in <x-admin-header /> (that is inside <head>) and not inside the sidebar (an offcanvas panel
     has a transform, which would break position: fixed).
     It also prints the server flash (session('status') / session('error')) through <x-toast.flash />.
     Settings come from config/toast.php. z-[1090] sits above the sticky admin menu bar (1030), Bootstrap's offcanvas
     and modals (1045 to 1055) and the custom dropdown menu (1080).
     pointer-events-none on the container: only the toasts themselves catch clicks, so the page under the empty
     strip stays usable. --}}
@php
$toast = config('toast');
$settings = [
'max' => $toast['max_visible'],
'mergeMs' => $toast['merge_window_ms'],
'durations' => $toast['durations_ms'],
'close' => $toast['labels']['close'],
'merged' => $toast['labels']['merged'],
];
@endphp
<div class="pointer-events-none fixed inset-x-0 top-0 z-[1090] flex flex-col items-center gap-3 px-4 pt-[calc(env(safe-area-inset-top,0px)+1rem)] min-[576px]:pt-[calc(env(safe-area-inset-top,0px)+1.5rem)]" role="region" aria-label="{{ $toast['labels']['region'] }}" data-toast-region data-toast-config="{{ json_encode($settings) }}"></div>
<x-toast.flash />