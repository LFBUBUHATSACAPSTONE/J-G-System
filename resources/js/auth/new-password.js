/**
 * New Password submit (AJAX) + live field feedback
 * 
 * Intercepts the new-password form so the modal isn't reloaded/reset.
 *  - live: password strength (names the missing requirement) + live
 *    match-check against confirm (as you type either field)
 *  - POSTs { token, email, password, password_confirmation } as JSON
 *    (token/email come from the verification step via setResetCredentials)
 *  - success: clears the fields and switches to the login view
 *  - failure: shows the error under the fields
 *
 * Expected back-end contract for `password.update`:
 *   200 JSON -> password updated
 *   422 {"message": "…", "errors": {"password": ["…"]}} -> rejected
 * `token` and `email` are returned by `verification.confirm` and held in
 * 
 * memory only; they are cleared on success and when the modal closes.
 */

import { setAuthView } from "../auth-modal.js";
import {
    getPasswordError,
    getMatchError,
    wireLiveField,
    setFieldState,
} from "./validation.js";

const AUTH_MODAL_ID = "authModal";
const VIEW_SELECTOR = '[data-view="new-password"]';

let resetCredentials = { token: "", email: "" };

function setResetCredentials({ token = "", email = "" } = {}) {
    resetCredentials = { token, email };
}

function initNewPassword() {
    const modalEl = document.getElementById(AUTH_MODAL_ID);
    if (!modalEl) return;

    const form = modalEl.querySelector(`${VIEW_SELECTOR} form`);
    if (form) {
        wireLiveField(form, "password", getPasswordError);

        // Confirm field needs to check equality against the live password
        // value, so it gets its own listeners rather than going through
        // wireLiveField (which only sees its own field's value).
        const passwordInput = form.querySelector('[name="password"]');
        const confirmInput = form.querySelector(
            '[name="password_confirmation"]',
        );
        const confirmError = form.querySelector(
            '[data-field-error="password_confirmation"]',
        );
        let confirmTouched = false;

        const checkMatch = () => {
            if (!confirmTouched) return;
            setFieldState(
                confirmInput,
                confirmError,
                getMatchError(confirmInput.value, passwordInput.value),
            );
        };
        confirmInput?.addEventListener("blur", () => {
            confirmTouched = true;
            checkMatch();
        });
        confirmInput?.addEventListener("input", checkMatch);
        // Also re-check confirm as the password itself changes (e.g. user
        // fixes the password after already filling in confirm), but only
        // once confirm has been touched — otherwise this would flag confirm
        // as empty/mismatched before the user has even reached it.
        passwordInput?.addEventListener("input", checkMatch);
    }

    modalEl.addEventListener("submit", (event) => {
        const target = event.target;
        if (!target.closest(VIEW_SELECTOR)) return;
        event.preventDefault();
        submitNewPassword(modalEl, target);
    });

    modalEl.addEventListener("input", (event) => {
        const target = event.target.closest("form");
        if (target?.closest(VIEW_SELECTOR)) clearError(target);
    });

    modalEl.addEventListener("hidden.bs.modal", () => {
        const target = modalEl.querySelector(`${VIEW_SELECTOR} form`);
        if (target) resetForm(target);
    });
}

async function submitNewPassword(modalEl, form) {
    const password = form.querySelector('input[name="password"]');
    const confirm = form.querySelector('input[name="password_confirmation"]');
    const submitBtn = form.querySelector('[type="submit"]');

    clearError(form);

    const passwordError = getPasswordError(password.value);
    if (passwordError) {
        showError(form, passwordError, [password]);
        password.focus();
        return;
    }

    const matchError = getMatchError(confirm.value, password.value);
    if (matchError) {
        showError(form, matchError, [confirm]);
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
                    "We couldn't update your password. Please try again.",
                [password],
            );
            return;
        }

        resetForm(form);
        setAuthView(modalEl, "login");
    } catch {
        showError(
            form,
            "Something went wrong on our end. Please check your connection and try again.",
        );
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
