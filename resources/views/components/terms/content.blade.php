{{--
  The Terms and Conditions text, rendered from config/terms.php. Used by the standalone page (user/terms.blade.php)
  and by the "terms" view of the auth modal (components/auth/terms-view.blade.php), so the two can never differ.
  Props: heading-tag  the tag of each section title (h2 on the page, whose h1 is the page title; h3 in the modal,
                      whose h2 is the view title);
         compact      slightly smaller type for the modal.
--}}
@props([
'headingTag' => 'h2',
'compact' => false,
])

@php
$terms = config('terms');

$introClass = $compact
? 'text-[0.85rem] leading-[1.7] text-auth-text-muted [margin:0_0_1rem]'
: 'text-[1rem] leading-[1.75] text-auth-text-muted [margin:0_0_1.5rem]';
$titleClass = $compact
? 'text-[0.95rem] font-semibold leading-[1.4] text-auth-text [margin:1.25rem_0_0.4rem]'
: 'text-[1.2rem] font-semibold leading-[1.4] text-auth-text [margin:2rem_0_0.6rem]';
$numberClass = 'text-auth-accent';
$paragraphClass = $compact
? 'text-[0.85rem] leading-[1.7] text-auth-text-muted [margin:0_0_0.6rem]'
: 'text-[1rem] leading-[1.75] text-auth-text-muted [margin:0_0_0.75rem]';
$listClass = $compact
? 'list-disc pl-[1.25rem] text-[0.85rem] leading-[1.7] text-auth-text-muted marker:text-auth-accent [margin:0_0_0.6rem]'
: 'list-disc pl-[1.5rem] text-[1rem] leading-[1.75] text-auth-text-muted marker:text-auth-accent [margin:0_0_0.75rem]';
$itemClass = '[margin:0_0_0.2rem]';
$taglineClass = $compact
? 'text-center text-[0.85rem] font-semibold text-auth-accent [margin:1.5rem_0_0.5rem]'
: 'text-center text-[1rem] font-semibold text-auth-accent [margin:2.5rem_0_0]';
@endphp

<div data-terms-version="{{ $terms['version'] }}">
  <p class="{{ $introClass }}">{{ $terms['intro'] }}</p>

  @foreach ($terms['sections'] as $section)
  <section>
    <{{ $headingTag }} class="{{ $titleClass }}">
      <span class="{{ $numberClass }}">{{ $loop->iteration }}.</span> {{ $section['title'] }}
    </{{ $headingTag }}>
    @foreach ($section['body'] as $block)
    @if (is_array($block))
    <ul class="{{ $listClass }}">
      @foreach ($block['list'] as $item)
      <li class="{{ $itemClass }}">{{ $item }}</li>
      @endforeach
    </ul>
    @else
    <p class="{{ $paragraphClass }}">{{ $block }}</p>
    @endif
    @endforeach
  </section>
  @endforeach

  <p class="{{ $taglineClass }}">{{ $terms['tagline'] }}</p>
</div>