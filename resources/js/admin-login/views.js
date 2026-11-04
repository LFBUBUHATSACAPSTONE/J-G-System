// View switching for the admin sign-in card. Same idea as setAuthView() in auth-modal.js, but the
// root is the card ([data-admin-auth]) instead of the #authModal Bootstrap modal.

export const ROOT_SELECTOR = "[data-admin-auth]";
const VIEW_SELECTOR = ".admin-login__view";
const DEFAULT_VIEW = "login";

export function getRoot() {
    return document.querySelector(ROOT_SELECTOR);
}

export function setView(root, view) {
    let matched = false;

    root.querySelectorAll(VIEW_SELECTOR).forEach((el) => {
        const isTarget = el.dataset.view === view;
        el.classList.toggle("d-none", !isTarget);
        if (isTarget) matched = true;
    });

    if (!matched) return setView(root, DEFAULT_VIEW);
    root.setAttribute("data-current-view", view);
}

// Focuses the first fillable field of the visible view.
export function focusFirstField(root) {
    const active = root.querySelector(`${VIEW_SELECTOR}:not(.d-none)`);
    active
        ?.querySelector(
            'input:not([type="hidden"]):not([type="checkbox"]):not([disabled])',
        )
        ?.focus();
}

export function initViews(root) {
    root.addEventListener("click", (event) => {
        const link = event.target.closest("[data-auth-view]");
        if (!link || !root.contains(link)) return;
        event.preventDefault();
        setView(root, link.getAttribute("data-auth-view"));
        focusFirstField(root);
    });
}
