/**
 * Forgot Password submit (AJAX)
 *
 * The form used to do a full-page POST, which reloads the page and resets
 * the modal to the login view. This intercepts the submit, posts via fetch,
 * and on success moves the modal to the verification view.
 *
 * Expected back-end contract for `password.email`:
 *   200 JSON (optional {"destination": "j***@mail.com"} -> code sent
 *   422 {"message": "…"}                                 -> failed
 * If `destination` is omitted, a masked version of the typed value is shown.
 */

import { setAuthView } from "./auth-modal.js";
import { setVerificationDestination } from "./verification-code.js";

const AUTH_MODAL_ID = "authModal";
const VIEW_SELECTOR = '[data-view="forgot-password"]';

function initForgotPassword() {
    const modalEl = document.getElementById(AUTH_MODAL_ID);
    if (!modalEl) return;

    modalEl.addEventListener("submit", (event) => {
        const form = event.target;
        if (!form.closest(VIEW_SELECTOR)) return;
        event.preventDefault();
        submitForgotPassword(modalEl, form);
    });

    modalEl.addEventListener("input", (event) => {
        const form = event.target.closest("form");
        if (form?.closest(VIEW_SELECTOR)) clearError(form);
    });

    modalEl.addEventListener("hidden.bs.modal", () => {
        const form = modalEl.querySelector(`${VIEW_SELECTOR} form`);
        if (form) clearError(form);
    });
}

async function submitForgotPassword(modalEl, form) {
    const input = form.querySelector('input[name="identifier"]');
    const identifier = input.value.trim();
    const submitBtn = form.querySelector('[type="submit"]');

    clearError(form);
    submitBtn.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    form.querySelector('input[name="_token"]')?.value ?? "",
            },
            body: JSON.stringify({ identifier }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            showError(
                form,
                data.message ||
                    "We could not find that account. Please try again.",
            );
            return;
        }

        const data = await response.json().catch(() => ({}));
        setVerificationDestination(
            data.destination || maskIdentifier(identifier),
        );
        setAuthView(modalEl, "verification");
        modalEl
            .querySelector('[data-view="verification"] .auth-modal__code-box')
            ?.focus();
    } catch {
        showError(form, "Something went wrong. Please try again.");
    } finally {
        submitBtn.disabled = false;
    }
}

function maskIdentifier(value) {
    if (value.includes("@")) {
        const [local, domain] = value.split("@");
        return `${local[0] ?? ""}***@${domain}`;
    }
    return `${"*".repeat(Math.max(value.length - 3, 0))}${value.slice(-3)}`;
}

function showError(form, message) {
    const errorEl = form.querySelector("[data-forgot-error]");
    if (!errorEl) return;
    errorEl.textContent = message;
    errorEl.classList.remove("d-none");
    form.querySelector('input[name="identifier"]').classList.add("is-invalid");
}

function clearError(form) {
    const errorEl = form.querySelector("[data-forgot-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    form.querySelector('input[name="identifier"]')?.classList.remove(
        "is-invalid",
    );
}

document.addEventListener("DOMContentLoaded", initForgotPassword);

export { initForgotPassword, maskIdentifier };
