@php
// Whole class strings (never built from pieces) so Tailwind can read them. Spacing is written with arbitrary
// values or properties on purpose: Bootstrap's `m-0`, `p-0`, `gap-*` and `px-*` are `!important` utilities with
// other values and can beat these (see tailwind-migration.md, "Plain class names that Bootstrap also defines").
$footerClass = 'w-full shrink-0 border-t-[1px] border-glass-border bg-landing-base px-[1.25rem] pt-[3.5rem] pb-[2.5rem] text-footer-text min-[576px]:px-[1.5rem] min-[576px]:pt-[3.75rem] min-[576px]:pb-[2.75rem] min-[992px]:px-[2rem] min-[992px]:pt-[4.5rem] min-[992px]:pb-[3.25rem]';
// < 992px one stacked column; 992-1199px two columns (Logo | Explore, the right block underneath); from 1200px three columns.
    $innerClass='mx-auto flex max-w-[1280px] flex-col gap-[2.75rem] min-[992px]:grid min-[992px]:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] min-[992px]:gap-x-[2.5rem] min-[992px]:gap-y-[2.75rem] min-[1200px]:grid-cols-[minmax(0,1.1fr)_minmax(0,0.6fr)_minmax(0,1.9fr)] min-[1200px]:gap-[2.5rem]' ;
    // Follow us + Information: stacked on phones, side by side from 640px; under Logo | Explore from 992px, the third column from 1200px.
    $rightClass='flex min-w-0 flex-col gap-[2rem] min-[640px]:flex-row min-[640px]:items-start min-[640px]:gap-[2.5rem] min-[992px]:col-span-full min-[1200px]:col-auto' ;
    $sectionClass='min-w-0 min-[640px]:flex-[1_1_50%]' ;
    $headingClass='text-[1.15rem] font-bold text-auth-text [margin:0_0_1.4rem]' ;
    $listClass='flex list-none flex-col gap-[1rem] [margin:0] [padding:0]' ;
    $linkClass='group/link inline-flex items-center gap-[0.6rem] text-[1rem] text-footer-link no-underline wrap-anywhere transition-[color,opacity] duration-200 ease-[ease] hover:text-auth-text hover:opacity-100' ;
    $staticClass='inline-flex items-center gap-[0.6rem] text-[1rem] text-footer-link no-underline' ;
    $iconClass='inline-flex shrink-0 items-center justify-center text-footer-link transition-colors duration-200 ease-[ease] group-hover/link:text-auth-text [&_svg]:size-[1.25rem]' ;
    @endphp

    <footer id="about" class="{{ $footerClass }}">
    <div class="{{ $innerClass }}">

        {{-- LEFT: Logo + copyright --}}
        <div class="min-w-0">
            <a href="{{ route('user.landing') }}#">
                <img src="{{ asset('images/logo/J&G_official_logo.webp') }}" alt="J&amp;G Audio Lights and Sounds" class="w-[12rem] min-[992px]:w-[14rem]">
            </a>

            <p class="block text-[0.9rem] text-footer-muted [margin:0.6rem_0_0]">
                &copy; {{ date('Y') }} J&amp;G Audio Lights and Sounds. All Rights Reserved.
            </p>
        </div>

        {{-- CENTER: Explore links --}}
        <div class="min-w-0">
            <h4 class="{{ $headingClass }}">Explore</h4>
            <ul class="{{ $listClass }}">
                <li><a href="{{ route('user.landing') }}#" class="{{ $linkClass }}">Home</a></li>
                <li><a href="{{ route('user.landing') }}#features" class="{{ $linkClass }}">Features</a></li>
                <li><a href="{{ route('user.landing') }}#packages" class="{{ $linkClass }}">Packages</a></li>
                <li><a href="{{ route('user.landing') }}#inclusions" class="{{ $linkClass }}">Inclusions</a></li>
                <li><a href="{{ route('user.landing') }}#about" class="{{ $linkClass }}">About</a></li>
            </ul>
        </div>

        {{-- RIGHT: Follow us + Information --}}
        <div class="{{ $rightClass }}">
            <div class="{{ $sectionClass }}">
                <h4 class="{{ $headingClass }}">Follow us</h4>
                <ul class="{{ $listClass }}">
                    <li>
                        <a href="https://www.facebook.com/profile.php?id=100093098290512&rdid=cU5tRkpB9NqXHNVU&share_url=https%3A%2F%2Fwww.facebook.com%2Fshare%2F1DijG2UTan#" target="_blank" rel="noopener noreferrer" class="{{ $linkClass }}">
                            <span class="{{ $iconClass }}">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                                    <path d="M13.5 21v-8h2.7l.4-3.2h-3.1V7.7c0-.9.25-1.5 1.55-1.5H16.7V3.3C16.4 3.26 15.4 3.17 14.24 3.17c-2.4 0-4.05 1.47-4.05 4.17v2.46H7.5v3.2h2.69V21h3.31Z" />
                                </svg>
                            </span>
                            <span>J&amp;G Audio Lights and Sounds</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://instagram.com/j3gaudio" target="_blank" rel="noopener noreferrer" class="{{ $linkClass }}">
                            <span class="{{ $iconClass }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18">
                                    <rect x="3" y="3" width="18" height="18" rx="5" />
                                    <circle cx="12" cy="12" r="4" />
                                    <circle cx="17.2" cy="6.8" r="1" />
                                </svg>
                            </span>
                            <span>J3gaudio</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.tiktok.com/@jandgaudio07?_r=1&_t=ZS-9A56G5vVo1K&fbclid=IwY2xjawUl4ipleHRuA2FlbQIxMABwZG9mBWJyaWQRMTNYT2cwNFRzMkFnd0pPUzZzcnRjBmFwcF9pZBAyMjIwMzkxNzg4MjAwODkyAAEeLR_oXy6tlA7ngPwifxKbrdFwu_XykaiN7ONdIp-s8u9oINd1Y_10F445_-4_aem_B19ocnaoqHF-8loznGN1_g" target="_blank" rel="noopener noreferrer" class="{{ $linkClass }}">
                            <span class="{{ $iconClass }}">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                                    <path d="M16.5 3c.3 1.8 1.4 3.2 3.5 3.5v2.7c-1.3 0-2.5-.4-3.5-1.1v6.4c0 2.9-2.3 5.2-5.2 5.2S6.1 15.4 6.1 12.5c0-2.7 2.1-5 4.8-5.2v2.8a2.4 2.4 0 1 0 2.4 2.4V3h3.2Z" />
                                </svg>
                            </span>
                            <span>Jgaudio07</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="{{ $sectionClass }}">
                <h4 class="{{ $headingClass }}">Information</h4>
                <ul class="{{ $listClass }}">
                    <li>
                        <a href="mailto:gianabanio7@gmail.com" class="{{ $linkClass }}">
                            <span class="{{ $iconClass }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18">
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <path d="m3 7 9 6 9-6" />
                                </svg>
                            </span>
                            <span>gianabanio7@gmail.com</span>
                        </a>
                    </li>
                    <li>
                        <span class="{{ $staticClass }}">
                            <span class="{{ $iconClass }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18">
                                    <path d="M12 21s7-6.1 7-11.5A7 7 0 0 0 5 9.5C5 14.9 12 21 12 21Z" />
                                    <circle cx="12" cy="9.5" r="2.3" />
                                </svg>
                            </span>
                            <span>Anyatam, San Ildefonso, Bulacan, Philippines</span>
                        </span>
                    </li>
                    <li>
                        <a href="tel:+639308721533" class="{{ $linkClass }}">
                            <span class="{{ $iconClass }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18">
                                    <path d="M4.5 3.5h3l1.5 4-2 1.5a12 12 0 0 0 6 6l1.5-2 4 1.5v3c0 1-.9 1.8-1.9 1.6C9.9 18.1 5.9 14.1 4.9 7.4 4.7 6.4 5.5 5.5 6.5 5.5" />
                                </svg>
                            </span>
                            <span>0930 872 1533</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
    </footer>