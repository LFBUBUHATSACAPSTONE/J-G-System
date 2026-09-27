
<footer class="jg-footer">
    <div class="jg-footer__inner">

        {{-- LEFT: Logo + copyright --}}
        <div class="jg-footer__col jg-footer__brand">
            <img src="{{ asset('images/logo/logo_rectangular.svg') }}" alt="J&amp;G Audio Lights and Sounds" class="jg-footer__logo-img">

            <p class="jg-footer__copyright">
                &copy; {{ date('Y') }} J&amp;G Audio Lights and Sounds. All Rights Reserved.
            </p>
        </div>

        {{-- CENTER: Explore links --}}
        <div class="jg-footer__col jg-footer__explore">
            <h4 class="jg-footer__heading">Explore</h4>
            <ul class="jg-footer__links">
                <li><a href="{{ url('/about') }}">About</a></li>
                <li><a href="{{ url('/features') }}">Features</a></li>
                <li><a href="{{ url('/packages') }}">Packages</a></li>
            </ul>
        </div>

        {{-- RIGHT: Follow us + Information --}}
        <div class="jg-footer__col jg-footer__right">
            <div class="jg-footer__right-section">
                <h4 class="jg-footer__heading">Follow us</h4>
                <ul class="jg-footer__links jg-footer__links--icon">
                    <li>
                        <a href="https://www.facebook.com/profile.php?id=100093098290512&rdid=cU5tRkpB9NqXHNVU&share_url=https%3A%2F%2Fwww.facebook.com%2Fshare%2F1DijG2UTan#" target="_blank" rel="noopener noreferrer">
                            <span class="jg-footer__icon">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M13.5 21v-8h2.7l.4-3.2h-3.1V7.7c0-.9.25-1.5 1.55-1.5H16.7V3.3C16.4 3.26 15.4 3.17 14.24 3.17c-2.4 0-4.05 1.47-4.05 4.17v2.46H7.5v3.2h2.69V21h3.31Z"/></svg>
                            </span>
                            <span>J&amp;G Audio Lights and Sounds</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://instagram.com/j3gaudio" target="_blank" rel="noopener noreferrer">
                            <span class="jg-footer__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1"/></svg>
                            </span>
                            <span>J3gaudio</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.tiktok.com/@jandgaudio07?_r=1&_t=ZS-9A56G5vVo1K&fbclid=IwY2xjawUl4ipleHRuA2FlbQIxMABwZG9mBWJyaWQRMTNYT2cwNFRzMkFnd0pPUzZzcnRjBmFwcF9pZBAyMjIwMzkxNzg4MjAwODkyAAEeLR_oXy6tlA7ngPwifxKbrdFwu_XykaiN7ONdIp-s8u9oINd1Y_10F445_-4_aem_B19ocnaoqHF-8loznGN1_g" target="_blank" rel="noopener noreferrer">
                            <span class="jg-footer__icon">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M16.5 3c.3 1.8 1.4 3.2 3.5 3.5v2.7c-1.3 0-2.5-.4-3.5-1.1v6.4c0 2.9-2.3 5.2-5.2 5.2S6.1 15.4 6.1 12.5c0-2.7 2.1-5 4.8-5.2v2.8a2.4 2.4 0 1 0 2.4 2.4V3h3.2Z"/></svg>
                            </span>
                            <span>Jgaudio07</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="jg-footer__right-section">
                <h4 class="jg-footer__heading">Information</h4>
                <ul class="jg-footer__links jg-footer__links--icon">
                    <li>
                        <a href="mailto:gianabanio7@gmail.com">
                            <span class="jg-footer__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                            </span>
                            <span>gianabanio7@gmail.com</span>
                        </a>
                    </li>
                    <li>
                        <span class="jg-footer__static">
                            <span class="jg-footer__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><path d="M12 21s7-6.1 7-11.5A7 7 0 0 0 5 9.5C5 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.3"/></svg>
                            </span>
                            <span>San Ildefonso, Bulacan, Philippines</span>
                        </span>
                    </li>
                    <li>
                        <a href="tel:+639308721533">
                            <span class="jg-footer__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><path d="M4.5 3.5h3l1.5 4-2 1.5a12 12 0 0 0 6 6l1.5-2 4 1.5v3c0 1-.9 1.8-1.9 1.6C9.9 18.1 5.9 14.1 4.9 7.4 4.7 6.4 5.5 5.5 6.5 5.5"/></svg>
                            </span>
                            <span>0930 872 1533</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</footer>

<style>
    .jg-footer {
        background-color: #090909;
        color: #e6e6e6;
        width: 100%;
        padding: 2.5rem 1.5rem 1.75rem;
        font-family: inherit;
        box-sizing: border-box;
        flex-shrink: 0;
    }
    .jg-footer * { box-sizing: border-box; }

    .jg-footer__inner {
        max-width: 1280px;
        margin: 0 auto;
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
    }

    .jg-footer__col { min-width: 0; }

    /* ---- Column widths (desktop) ---- */
    .jg-footer__brand  { flex: 1 1 32%; max-width: 32%; }
    .jg-footer__explore{ flex: 1 1 20%; max-width: 20%; }
    .jg-footer__right  {
        flex: 1 1 40%;
        max-width: 48%;
        display: flex;
        gap: 2.5rem;
        flex-wrap: nowrap;
        align-items: flex-start;
    }
    .jg-footer__right-section {
        flex: 1 1 50%;
        min-width: 0;
    }

    /* ---- Logo ---- */
    .logo_final.png {
        height: 250px;
        width: auto;
        display: block;
    }

    .jg-footer__copyright {
        display: block;
        font-size: 0.8rem;
        color: #9a9a9a;
        margin: 0.35rem 0 0;
    }

    /* ---- Headings ---- */
    .jg-footer__heading {
        font-size: 1rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0 0 1.1rem;
    }

    /* ---- Links ---- */
    .jg-footer__links {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
    }
    .jg-footer__links a,
    .jg-footer__static {
        color: #cfcfcf;
        text-decoration: none;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        transition: color 0.2s ease, opacity 0.2s ease;
    }
    .jg-footer__links a:hover {
        color: #ffffff;
        opacity: 1;
    }
    .jg-footer__links--icon .jg-footer__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #cfcfcf;
        flex-shrink: 0;
        transition: color 0.2s ease;
    }
    .jg-footer__links--icon a:hover .jg-footer__icon { color: #ffffff; }

    /* ---- Responsive ---- */
    @media (max-width: 900px) {
        .jg-footer__brand,
        .jg-footer__explore,
        .jg-footer__right {
            max-width: 100%;
            flex: 1 1 100%;
        }
        .jg-footer__right { gap: 2.5rem; }
    }

    @media (max-width: 640px) {
        .jg-footer__right {
            flex-wrap: wrap;
        }
        .jg-footer__right-section {
            flex: 1 1 100%;
        }
    }

    @media (max-width: 560px) {
        .jg-footer { padding: 2.5rem 1.25rem 1.75rem; }
        .jg-footer__inner {
            flex-direction: column;
            gap: 2.25rem;
        }
        /* Order: Logo/copyright -> Explore -> Follow us -> Information */
        .jg-footer__brand   { order: 1; }
        .jg-footer__explore { order: 2; }
        .jg-footer__right   { order: 3; flex-direction: column; flex-wrap: nowrap; gap: 2rem; }
        .jg-footer__right-section { flex: 1 1 auto; }
    }

    /* ---- Sticky footer: fills leftover space on short pages ---- */
    html, body {
        height: 100%;
    }
    #app {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    #app > main {
        flex: 1 0 auto;
    }
</style>