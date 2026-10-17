/**
 * Admin New Password (AJAX) + live field feedback. Same as auth/new-password.js:
 *  - live password-strength message (names the missing requirement) and live match check
 *  - POSTs {token, email, password, password_confirmation} as JSON
 *  - success: clears the form and returns to the login view
 *
 * Back-end contract for `admin.password.update`:
 *   200 {message?}                          -> password updated
 *   422 {message, errors{password}}         -> rejected
 *   429 {message}                           -> too many attempts
 * `token` and `email` come from `admin.verification.confirm` and are held in memory only.
 */

import { getCsrfToken } from "../auth/csrf.js";
import {
    getPasswordError,
    getMatchError,
    wireLiveFieldImmediate,
    clearInvalidWithoutFieldError,
    clearAllFieldStates,
    setFieldState,
} from "../auth/validation.js";
import { setView, focusFirstField } from "./views.js";

const FORM_SELECTOR = "[data-admin-new-password]";

let resetCredentials = { token: "", email: "" };

export function setResetCredentials({ token = "", email = "" } = {}) {
    resetCredentials = { token, email };
}

export function initNewPassword(root) {
    const form = root.querySelector(FORM_SELECTOR);
    if (!form) return;

    wireLiveFieldImmediate(form, "password", getPasswordError);

    const passwordInput = form.querySelector('[name="password"]');
    const confirmInput = form.querySelector('[name="password_confirmation"]');
    const confirmError = form.querySelector(
        '[data-field-error="password_confirmation"]',
    );
    const checkMatch = () =>
        setFieldState(
            confirmInput,
            confirmError,
            getMatchError(confirmInput.value, passwordInput.value),
        );
    confirmInput.addEventListener("input", checkMatch);
    // Re-check confirm when the password changes, but only once confirm has a value.
    passwordInput.addEventListener("input", () => {
        if (confirmInput.value) checkMatch();
    });

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitNewPassword(root, form);
    });
    form.addEventListener("input", () => clearError(form));
}

async function submitNewPassword(root, form) {
    const password = form.querySelector('input[name="password"]');
    const confirm = form.querySelector('input[name="password_confirmation"]');
    const submitBtn = form.querySelector('[type="submit"]');

    clearError(form);

    const passwordError = getPasswordError(password.value);
    if (passwordError) {
        setFieldState(
            password,
            form.querySelector('[data-field-error="password"]'),
            passwordError,
        );
        password.focus();
        return;
    }

    const matchError = getMatchError(confirm.value, password.value);
    if (matchError) {
        setFieldState(
            confirm,
            form.querySelector('[data-field-error="password_confirmation"]'),
            matchError,
        );
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
                "X-CSRF-TOKEN": getCsrfToken(form),
            },
            body: JSON.stringify({
                token: resetCredentials.token,
                email: resetCredentials.email,
                password: password.value,
                password_confirmation: confirm.value,
            }),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
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
        setView(root, "login");
        focusFirstField(root);
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
    clearInvalidWithoutFieldError(form);
}

function resetForm(form) {
    setResetCredentials();
    form.reset();
    clearError(form);
    clearAllFieldStates(form);
}
