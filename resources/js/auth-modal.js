const AUTH_MODAL_ID = "authModal";
const VIEW_SELECTOR = ".auth-modal__view";
const DEFAULT_VIEW = "login";

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

    modalEl.addEventListener("click", (event) => {
        const link = event.target.closest("[data-auth-view]");
        if (!link || !modalEl.contains(link)) return;
        if (link.hasAttribute("data-bs-toggle")) return;
        event.preventDefault();
        setAuthView(modalEl, link.getAttribute("data-auth-view"));
    });

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
    }
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

    const icon = btn.querySelector("img");

    if (icon) {
        icon.src = show
            ? "/images/icons/auth/hide_password.svg"
            : "/images/icons/auth/show_password.svg";
        icon.alt = show ? "Hide Password" : "Show Password";
    }
    btn.setAttribute("aria-pressed", String(show));
}

document.addEventListener("DOMContentLoaded", initAuthModal);

export { initAuthModal, setAuthView, togglePasswordVisibility };
