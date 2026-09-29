import { Collapse } from "bootstrap";

/**
 * Navbar in-page links (Home / Feature / Package -> # / #features / #packages).
 *
 * Scrolling itself is left to the browser (CSS `scroll-behavior: smooth`
 * in _utilities.scss, `scroll-padding-top` in _navigation.scss), so this
 * file never calls preventDefault() on the links. What it owns:
 *
 *  1. ACTIVE STATE — exactly one link carries `.active` + aria-current.
 *     - Clicking a link makes it active immediately.
 *     - While the page is scrolling toward that link's section (smooth
 *       scroll passes through other sections) the scroll-spy is paused,
 *       so the highlight doesn't flicker through Feature on the way to
 *       Package. It resumes on `scrollend`, on user wheel/touch/key input,
 *       or after a fallback timeout.
 *     - Otherwise a scroll-spy picks the section currently under the
 *       sticky header; above the first section, Home is active.
 *
 *  2. MOBILE MENU — the links live in the collapsed #nav; Bootstrap won't
 *     close it on link click, so we hide it. We deliberately do NOT use
 *     data-bs-toggle="collapse" on the links: Bootstrap's collapse plugin
 *     calls preventDefault() on any <a> it toggles, which would cancel
 *     the link's own navigation.
 */

const NAV_ID = "nav";
const LINK_SELECTOR = "a.nav-link[data-nav-target]";
const HOME_TARGET = "home";
const LOCK_FALLBACK_MS = 1200;

function initNavigation() {
    const navEl = document.getElementById(NAV_ID);
    if (!navEl) return;

    const links = Array.from(navEl.querySelectorAll(LINK_SELECTOR));
    if (!links.length) return;

    const header = navEl.closest("header");

    // target name -> section element (Home has no element: it's page top).
    const sections = links
        .map((link) => link.dataset.navTarget)
        .filter((name) => name !== HOME_TARGET)
        .map((name) => ({ name, el: document.getElementById(name) }))
        .filter((s) => s.el);

    let lockTimer = null;
    let locked = false;

    function setActive(name) {
        links.forEach((link) => {
            const isActive = link.dataset.navTarget === name;
            link.classList.toggle("active", isActive);
            if (isActive) {
                link.setAttribute("aria-current", "location");
            } else {
                link.removeAttribute("aria-current");
            }
        });
    }

    function unlock() {
        locked = false;
        clearTimeout(lockTimer);
        updateFromScroll();
    }

    function lock() {
        locked = true;
        clearTimeout(lockTimer);
        lockTimer = setTimeout(unlock, LOCK_FALLBACK_MS);
    }

    function updateFromScroll() {
        if (locked) return;

        const headerHeight = header ? header.offsetHeight : 0;
        // A section counts as "current" once its top has passed a line
        // just below the sticky header.
        const line = headerHeight + window.innerHeight * 0.25;
        const atBottom =
            window.innerHeight + window.scrollY >=
            document.documentElement.scrollHeight - 2;

        let current = HOME_TARGET;
        for (const { name, el } of sections) {
            if (el.getBoundingClientRect().top <= line) current = name;
        }

        // Short last section that can never reach the line: at the very
        // bottom of the page, the last section wins.
        if (atBottom && sections.length) {
            const last = sections[sections.length - 1];
            if (last.el.getBoundingClientRect().bottom > 0) current = last.name;
        }

        setActive(current);
    }

    let ticking = false;
    window.addEventListener(
        "scroll",
        () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => {
                ticking = false;
                updateFromScroll();
            });
        },
        { passive: true },
    );
    window.addEventListener("resize", updateFromScroll);
    window.addEventListener("hashchange", updateFromScroll);

    // Smooth scroll finished (or the person took over) -> resume the spy.
    window.addEventListener("scrollend", () => {
        if (locked) unlock();
    });
    ["wheel", "touchstart", "keydown"].forEach((type) =>
        window.addEventListener(
            type,
            () => {
                if (locked) unlock();
            },
            { passive: true },
        ),
    );

    navEl.addEventListener("click", (event) => {
        const link = event.target.closest(LINK_SELECTOR);
        if (!link) return;

        setActive(link.dataset.navTarget);
        lock();

        if (navEl.classList.contains("show")) {
            Collapse.getOrCreateInstance(navEl).hide();
        }
    });

    // Brand logo -> back to top, so Home should light up.
    document.querySelectorAll("header a.navbar-brand").forEach((brand) =>
        brand.addEventListener("click", () => {
            setActive(HOME_TARGET);
            lock();
        }),
    );

    // Initial state (covers page load with a #hash or restored scroll).
    window.addEventListener("load", updateFromScroll);
    updateFromScroll();
}

document.addEventListener("DOMContentLoaded", initNavigation);
