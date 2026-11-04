{{-- Inline SVG icon (24px grid, stroke = currentColor). The chat is used on pages that load no icon
     font (the user landing page), so it carries its own icons. Size with font-size on the parent. --}}
@props(['name'])

@php
$paths = [
'chat' => '
<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z" />',
'x' => '
<path d="M18 6 6 18" />
<path d="m6 6 12 12" />',
'paperclip' => '
<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48" />',
'send' => '
<path d="m22 2-7 20-4-9-9-4Z" />
<path d="M22 2 11 13" />',
'user' => '
<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
<circle cx="12" cy="7" r="4" />',
'file' => '
<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
<path d="M14 2v4a2 2 0 0 0 2 2h4" />',
'chevron-left' => '
<path d="m15 18-6-6 6-6" />',
'phone' => '
<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />',
'music' => '
<path d="M9 18V5l12-2v13" />
<circle cx="6" cy="18" r="3" />
<circle cx="18" cy="16" r="3" />',
'calendar' => '
<path d="M8 2v4" />
<path d="M16 2v4" />
<rect width="18" height="18" x="3" y="4" rx="2" />
<path d="M3 10h18" />',
'clock' => '
<circle cx="12" cy="12" r="10" />
<path d="M12 6v6l4 2" />',
'map-pin' => '
<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
<circle cx="12" cy="10" r="3" />',
'search' => '
<circle cx="11" cy="11" r="8" />
<path d="m21 21-4.3-4.3" />',
];
@endphp

<svg class="chat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $paths[$name] ?? '' !!}</svg>