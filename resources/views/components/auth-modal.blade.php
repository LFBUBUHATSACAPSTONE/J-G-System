{{--
    Auth modal shell (Phase 7B): the styles are utilities, the class strings are the PHP variables set just below.
    Bootstrap's modal and carousel JS still drive it, so their class names and every data-bs-* attribute stay.
    Hooks kept: .auth-modal__view (auth-modal.js), d-none (auth-modal.js and the forms), .active on the carousel dots.
    Plain spacing and border names are written as arbitrary values ([padding:0], [margin:0], [border:none]) because
    Bootstrap's p-*, m-*, border are !important utilities with other values (docs/tailwind-migration.md, Conventions).
--}}
@php
$dialogClass = 'w-[calc(100%-2rem)] max-w-[880px] [margin:1rem_auto] min-[560px]:w-[calc(100%-8rem)] min-[560px]:[margin:1.25rem_auto] min-[768px]:w-[calc(100%-3rem)] min-[768px]:[margin:1.5rem_auto]';
$contentClass = 'relative isolate max-h-[calc(100vh-3rem)] overflow-hidden rounded-card bg-auth-surface [border:none] before:pointer-events-none before:absolute before:inset-[-20%_-20%_auto_auto] before:z-[-1] before:h-[60%] before:w-[70%] before:bg-[radial-gradient(circle_at_top_right,var(--color-auth-glow-violet)_0%,var(--color-auth-glow-rose)_45%,transparent_75%)] before:opacity-35 before:blur-[60px]';
$headerClass = 'absolute top-3 right-3 z-2 [border:none] [padding:0]';
$closeClass = 'box-border size-9 rounded-full bg-transparent bg-none [margin:0] [padding:0] opacity-100 transition-[background-color] duration-150 ease-[ease] hover:bg-auth-overlay-soft focus:shadow-none focus:outline-none focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus)] before:absolute before:inset-0 before:rounded-[inherit] before:[background:var(--bs-btn-close-bg)_center/0.9em_auto_no-repeat] before:[filter:invert(1)_grayscale(1)_brightness(2)] before:opacity-70 before:transition-[opacity] before:duration-150 before:ease-[ease] hover:before:opacity-100 focus-visible:before:opacity-100';
$bodyClass = 'flex max-h-[calc(100vh-3rem)] flex-col [padding:0] min-[768px]:min-h-[540px] min-[768px]:flex-row';
$leftClass = 'relative hidden shrink-0 min-[768px]:block min-[768px]:w-[44%] min-[768px]:p-[1rem]';
$carouselClass = 'h-[160px] overflow-hidden rounded-panel min-[768px]:h-full';
$innerClass = 'h-full rounded-panel';
$itemClass = 'h-full rounded-panel after:pointer-events-none after:absolute after:inset-[auto_0_0_0] after:h-[45%] after:bg-[linear-gradient(to_top,var(--color-auth-scrim)_0%,var(--color-auth-scrim-clear)_100%)]';
$imgClass = 'h-full w-full object-cover';
$taglineClass = 'absolute bottom-8 left-1/2 z-1 w-max max-w-[calc(100%-2.5rem)] -translate-x-1/2 [margin:0] text-center text-fluid-tagline leading-[1.3] font-semibold wrap-break-word text-white [text-shadow:0_2px_10px_var(--color-shadow)]';
$indicatorsClass = 'absolute bottom-3 [margin:0]';
$dotClass = 'h-[3px] w-[20px] rounded-full bg-auth-overlay-faint opacity-100 [border:none] [&.active]:w-[28px] [&.active]:bg-white';
$rightClass = 'flex-1 overflow-y-auto [padding:1.5rem_1.5rem_2rem] text-auth-text [scrollbar-width:none] [&::-webkit-scrollbar]:hidden min-[768px]:flex min-[768px]:flex-col min-[768px]:[&>[data-view]]:my-auto min-[768px]:[padding:2rem_2.75rem]';
@endphp
<div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true" data-current-view="login">
    <div class="modal-dialog modal-dialog-centered {{ $dialogClass }}">
        <div class="modal-content {{ $contentClass }}">

            <div class="modal-header {{ $headerClass }}">
                <button type="button" class="btn-close {{ $closeClass }}" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body {{ $bodyClass }}">

                {{-- Left panel: carousel (shared across all views) --}}
                <div class="{{ $leftClass }}">
                    <div id="authCarousel" class="carousel slide {{ $carouselClass }}" data-bs-ride="carousel">
                        <div class="carousel-inner {{ $innerClass }}">
                            <div class="carousel-item active {{ $itemClass }}">
                                <img src="{{ asset('images/carousel/3.webp') }}" alt="" class="{{ $imgClass }}">
                                <p class="{{ $taglineClass }}">Plan it. Light it. Sound it.</p>
                            </div>
                            <div class="carousel-item {{ $itemClass }}">
                                <img src="{{ asset('images/carousel/5.webp') }}" alt="" class="{{ $imgClass }}">
                                <p class="{{ $taglineClass }}">Plan loud. Shine bright.</p>
                            </div>
                            <div class="carousel-item {{ $itemClass }}">
                                <img src="{{ asset('images/carousel/7.webp') }}" alt="" class="{{ $imgClass }}">
                                <p class="{{ $taglineClass }}">Bright beats. Smooth events.</p>
                            </div>
                        </div>
                        <div class="carousel-indicators {{ $indicatorsClass }}">
                            <button type="button" data-bs-target="#authCarousel" data-bs-slide-to="0" class="active {{ $dotClass }}"></button>
                            <button type="button" data-bs-target="#authCarousel" data-bs-slide-to="1" class="{{ $dotClass }}"></button>
                            <button type="button" data-bs-target="#authCarousel" data-bs-slide-to="2" class="{{ $dotClass }}"></button>
                        </div>
                    </div>
                </div>

                {{-- Right panel: swappable form views --}}
                <div class="{{ $rightClass }}">

                    {{-- View: Login --}}
                    <div class="auth-modal__view" data-view="login">
                        @include('components.auth.login-form')
                    </div>

                    {{-- View: Sign Up --}}
                    <div class="auth-modal__view d-none" data-view="signup">
                        @include('components.auth.signup-form')
                    </div>

                    {{-- View: Forgot Password --}}
                    <div class="auth-modal__view d-none" data-view="forgot-password">
                        @include('components.auth.forgot-password-form')
                    </div>

                    {{-- View: Verification --}}
                    <div class="auth-modal__view d-none" data-view="verification">
                        @include('components.auth.verification-form')
                    </div>

                    {{-- View: New Password --}}
                    <div class="auth-modal__view d-none" data-view="new-password">
                        @include('components.auth.new-password-form')
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>