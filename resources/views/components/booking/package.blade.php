@props(['packages' => []])


{{--Package Card Parent Containers--}}
<div id="packages" class="relative isolate z-1 bg-landing-base px-(--section-pad-x) pt-(--section-pad-top) pb-(--section-pad-bottom)" data-package-selection>
    {{-- Red gradient behind the section (cover), and the lower half of the Features star continuing across the seam
         (star_full.webp is 1409x1432; the box is 1409x592 and shows its bottom). Both sit under the content. --}}
    <img src="{{ asset('images/backgrounds/design/package_red_gradient.webp') }}" alt="" aria-hidden="true" class="pointer-events-none absolute inset-0 -z-1 size-full object-cover object-center">
    <img src="{{ asset('images/backgrounds/design/star_full.webp') }}" alt="" aria-hidden="true" class="pointer-events-none absolute top-0 left-0 -z-1 aspect-[1409/592] w-full object-cover object-bottom">

    <h2 class="mt-0 mb-7 text-center font-heading text-[length:clamp(1.4rem,1rem_+_2vw,2.25rem)] leading-[1.2] font-extrabold tracking-[0.12em] text-features-title uppercase [text-shadow:0_2px_12px_oklch(0%_0_0/0.45)] min-[768px]:mb-9">
        Packages
    </h2>

    <div class="mx-auto grid max-w-[26rem] grid-cols-1 gap-[1rem] min-[768px]:max-w-[46rem] min-[768px]:grid-cols-2 min-[768px]:gap-5 min-[992px]:max-w-[1100px] min-[992px]:grid-cols-3 min-[992px]:gap-6" id="package-grid">
        @foreach ($packages as $package)
        <x-package-card
            :name="$package['name']"
            :price="$package['price']"
            :features="$package['features']"
            :package-id="$package['id']"
            :featured="$package['featured'] ?? false" />
        @endforeach
    </div>
</div>