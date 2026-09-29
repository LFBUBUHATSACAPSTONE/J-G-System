import { Collapse } from "bootstrap";

/**
 * Navbar in-page links (Home / Feature / Package -> # / #features / #packages).
 *
 * The only thing this adds: on mobile the links live inside the collapsed menu (#nav), and Bootstrap doesn't close that menu on its own when a nav-link is clicked. So if the menu is open when a link inside it is clicked, we hide it.
 *  We deliberately do NOT use  data-bs-toggle="collapse" on the links for this — Bootstrap's collapse plugin calls preventDefault() on any <a> it toggles, which would cancel the link's own navigation on every breakpoint, not just mobile.
 */

const NAV_ID = "nav";
const LINK_SELECTOR = "a.nav-link";

function initNavigation() {
    const navEl = document.getElementById(NAV_ID);
    if (!navEl) return;

    navEl.addEventListener("click", (event) => {
        const link = event.target.closest(LINK_SELECTOR);
        if (!link || !navEl.classList.contains("show")) return;

        Collapse.getOrCreateInstance(navEl).hide();
    });
}

document.addEventListener("DOMContentLoaded", initNavigation);