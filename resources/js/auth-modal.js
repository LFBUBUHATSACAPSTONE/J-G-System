const AUTH_MODAL_ID = "authModal";
const VIEW_SELECTOR = ".auth-modal__view";
const DEFAULT_VIEW = "login";
const TERMS_VIEW = "terms";
const SIGNUP_VIEW = "signup";
// How many pixels short of the very end still counts as "at the end" (browser zoom and sub-pixel rounding).
const TERMS_END_TOLERANCE = 4;

function initAuthModal() {
    const modalEl = document.getElementById(AUTH_MODAL_ID);
    if (!modalEl) return;

    modalEl.addEventListener("show.bs.modal", (event) => {
        const trigger = event.relatedTarget;
        const view = trigger?.getAttribute("data-auth-view") || DEFAULT_VIEW;
        setAuthView(modalEl, view);
    });

    modalEl.addEventListener("hidden.bs.modal", () => {
        setAuthView(modalEl, DEFAULT_VIEW);
    });

    modalEl.addEventListener("shown.bs.modal", () => {
        focusFirstField(modalEl);
        syncTermsAccept(modalEl);
    });

    modalEl.addEventListener("click", (event) => {
        const link = event.target.closest("[data-auth-view]");
        if (!link || !modalEl.contains(link)) return;
        if (link.hasAttribute("data-bs-toggle")) return;
        // A real link (the Terms link has an href) keeps its normal behaviour on Ctrl/Cmd/Shift-click or middle-click: it opens the page in a new tab.
        if (
            link.tagName === "A" &&
            (event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey ||
                event.button !== 0)
        )
            return;
        event.preventDefault();
        setAuthView(modalEl, link.getAttribute("data-auth-view"));
        focusFirstField(modalEl);
    });

    initTermsView(modalEl);

    initPasswordToggles(modalEl);
}

function setAuthView(modalEl, view) {
    const views = modalEl.querySelectorAll(VIEW_SELECTOR);
    let matched = false;

    views.forEach((el) => {
        const isTarget = el.dataset.view === view;
        el.classList.toggle("d-none", !isTarget);
        if (isTarget) matched = true;
    });

    modalEl.setAttribute("data-current-view", matched ? view : DEFAULT_VIEW);
    if (!matched) {
        setAuthView(modalEl, DEFAULT_VIEW);
        return;
    }

    // The Terms text always opens at its top, not where it was left, and "I have read and agree" starts disabled again. If the whole text fits without scrolling (a very tall screen) the check right after unlocks it.
    if (view === TERMS_VIEW) {
        const scroll = modalEl.querySelector("[data-terms-scroll]");
        if (scroll) scroll.scrollTop = 0;
        setTermsAcceptEnabled(modalEl, false);
        syncTermsAccept(modalEl);
    }
}

// "I have read and agree" is disabled until the Terms text has been scrolled to its end. Once reached it stays enabled until the view is opened again.
function setTermsAcceptEnabled(modalEl, enabled) {
    const accept = modalEl.querySelector("[data-terms-accept]");
    const hint = modalEl.querySelector("[data-terms-hint]");
    if (accept) accept.disabled = !enabled;
    if (hint) {
        hint.textContent = enabled
            ? hint.dataset.unlockedText || ""
            : hint.dataset.lockedText || "";
    }
}

// Enables the button when the text is scrolled to the end, or has nothing to scroll. Does nothing while the view is hidden (nothing can be measured then).
function syncTermsAccept(modalEl) {
    if (modalEl.getAttribute("data-current-view") !== TERMS_VIEW) return;
    const scroll = modalEl.querySelector("[data-terms-scroll]");
    const accept = modalEl.querySelector("[data-terms-accept]");
    if (!scroll || !accept || !accept.disabled) return;
    if (scroll.clientHeight === 0) return;

    const atEnd =
        scroll.scrollTop + scroll.clientHeight >=
        scroll.scrollHeight - TERMS_END_TOLERANCE;
    if (atEnd) setTermsAcceptEnabled(modalEl, true);
}

// Focuses the first fillable, visible input/textarea/select in the currently active view; a view with no field (Terms) focuses its heading (`data-view-heading`, tabindex -1) so a keyboard or screen-reader user lands on it. Safe to call even if the modal isn't shown yet (it just no-ops when there's nothing focusable).
function focusFirstField(modalEl) {
    const activeView = modalEl.querySelector(`${VIEW_SELECTOR}:not(.d-none)`);
    if (!activeView) return;

    const field = activeView.querySelector(
        'input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled])',
    );
    if (field) {
        field.focus();
        return;
    }
    activeView.querySelector("[data-view-heading]")?.focus();
}

// Terms and Conditions view: "I have read and agree" ticks the sign-up checkbox (through a `change` event, so signup.js clears the "please agree" message) and returns to sign-up; Esc steps back to sign-up instead of closing the whole modal.
function initTermsView(modalEl) {
    const scroll = modalEl.querySelector("[data-terms-scroll]");
    // `scroll` does not bubble, so it is listened to on the text area itself; a resize can make the text fit (or not) without any scrolling.
    scroll?.addEventListener("scroll", () => syncTermsAccept(modalEl), {
        passive: true,
    });
    window.addEventListener("resize", () => syncTermsAccept(modalEl));

    modalEl.addEventListener("click", (event) => {
        const accept = event.target.closest("[data-terms-accept]");
        if (!accept || !modalEl.contains(accept) || accept.disabled) return;

        const checkbox = modalEl.querySelector(
            `[data-view="${SIGNUP_VIEW}"] [name="terms"]`,
        );
        if (checkbox && !checkbox.checked) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event("change", { bubbles: true }));
        }
        setAuthView(modalEl, SIGNUP_VIEW);
        checkbox?.focus();
    });

    // Capture phase on the modal root: it runs before Bootstrap's own Esc handler, which would close the modal.
    modalEl.addEventListener(
        "keydown",
        (event) => {
            if (event.key !== "Escape") return;
            if (modalEl.getAttribute("data-current-view") !== TERMS_VIEW)
                return;
            event.preventDefault();
            event.stopImmediatePropagation();
            setAuthView(modalEl, SIGNUP_VIEW);
            focusFirstField(modalEl);
        },
        true,
    );
}

function initPasswordToggles(modalEl) {
    modalEl.addEventListener("click", (event) => {
        const btn = event.target.closest(".auth-modal__toggle-password");
        if (!btn || !modalEl.contains(btn)) return;
        togglePasswordVisibility(btn);
    });
}

function togglePasswordVisibility(btn) {
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    const show = input.type === "password";
    input.type = show ? "text" : "password";

    const icon = btn.querySelector("i");
    if (icon) icon.className = `ph ph-${show ? "eye-slash" : "eye"}`;
    btn.setAttribute("aria-label", show ? "Hide password" : "Show password");
    btn.setAttribute("aria-pressed", String(show));
}

document.addEventListener("DOMContentLoaded", initAuthModal);

export {
    initAuthModal,
    setAuthView,
    togglePasswordVisibility,
    focusFirstField,
    initTermsView,
    syncTermsAccept,
};
