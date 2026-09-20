/**
 * New Password submit (AJAX)
 *
 * Intercepts the new-password form so the modal isn't reloaded/reset.
 *  - client-side check: both fields must match
 *  - POSTs { token, email, password, password_confirmation } as JSON (token/email come from the verification step via setResetCredentials)
 *
 *  - success: clears the fields and switches to the login view
 *  - failure: shows the error under the fields
 *
 * Expected back-end contract for `password.update`:
 *   200 JSON
 * -> password updated
 *   422 {"message": "…", "errors": {"password": ["…"]}}
 * -> rejected
 *
 * `token` and `email` are returned by `verification.confirm` and held in
 *
 * memory only; they are cleared on success and when the modal closes.
 */

import { setAuthView } from "../auth-modal.js";

const AUTH_MODAL_ID = "authModal";
const VIEW_SELECTOR = '[data-view="new-password"]';

let resetCredentials = { token: "", email: "" };

function setResetCredentials({ token = "", email = "" } = {}) {
    resetCredentials = { token, email };
}

function initNewPassword() {
    const modalEl = document.getElementById(AUTH_MODAL_ID);
    if (!modalEl) return;

    modalEl.addEventListener("submit", (event) => {
        const form = event.target;
        if (!form.closest(VIEW_SELECTOR)) return;
        event.preventDefault();
        submitNewPassword(modalEl, form);
    });

    modalEl.addEventListener("input", (event) => {
        const form = event.target.closest("form");
        if (form?.closest(VIEW_SELECTOR)) clearError(form);
    });

    modalEl.addEventListener("hidden.bs.modal", () => {
        const form = modalEl.querySelector(`${VIEW_SELECTOR} form`);
        if (form) resetForm(form);
    });
}

async function submitNewPassword(modalEl, form) {
    const password = form.querySelector('input[name="password"]');
    const confirm = form.querySelector('input[name="password_confirmation"]');
    const submitBtn = form.querySelector('[type="submit"]');

    clearError(form);

    if (password.value !== confirm.value) {
        showError(form, "Passwords do not match.", [confirm]);
        confirm.focus();
        return;
    }

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
            body: JSON.stringify({
                token: resetCredentials.token,
                email: resetCredentials.email,
                password: password.value,
                password_confirmation: confirm.value,
            }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            showError(
                form,
                Object.values(data.errors ?? {})[0]?.[0] ||
                    data.message ||
                    "Could not update your password. Please try again.",
                [password],
            );
            return;
        }

        resetForm(form);
        setAuthView(modalEl, "login");
    } catch {
        showError(form, "Something went wrong. Please try again.");
    } finally {
        submitBtn.disabled = false;
    }
}

function showError(form, message, invalidInputs = []) {
    const errorEl = form.querySelector("[data-new-password-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
    invalidInputs.forEach((input) => input.classList.add("is-invalid"));
}

function clearError(form) {
    const errorEl = form.querySelector("[data-new-password-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    form.querySelectorAll("input").forEach((input) =>
        input.classList.remove("is-invalid"),
    );
}

function resetForm(form) {
    setResetCredentials();
    form.reset();
    clearError(form);
}

document.addEventListener("DOMContentLoaded", initNewPassword);

export { initNewPassword, setResetCredentials };
