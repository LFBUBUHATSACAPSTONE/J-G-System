// Admin sign-in entry (its own Vite entry: no sidebar, tables or calendar code ships here).
//
// Back-end contract for `admin.login.submit` (POST JSON {email, password, remember}):
//   200 {redirect?}                       -> signed in; goes to `redirect`, else the form's data-redirect
//   422 {message, errors{email|password}} -> rejected; field messages show under the fields
//   403 {message}                         -> signed in but not an admin ("no access")
//   429 {message}                         -> too many attempts
// Same helpers and error pattern as the user auth modal (auth/login.js).

import "@phosphor-icons/web/regular";

import { getCsrfToken } from "./auth/csrf.js";
import {
    getEmailError,
    getRequiredError,
    wireLiveField,
    clearInvalidWithoutFieldError,
    setFieldState,
} from "./auth/validation.js";
import { togglePasswordVisibility } from "./auth-modal.js";

const FORM_SELECTOR = "[data-admin-login]";

const MESSAGES = {
    credentials:
        "We couldn't sign you in. Check that your email and password are correct.",
    noAccess: "This account doesn't have admin access.",
    throttled: "Too many attempts. Please wait a moment and try again.",
    network:
        "Something went wrong on our end. Please check your connection and try again.",
};

const getPasswordRequired = (value) => getRequiredError(value, "Password");

function initAdminLogin() {
    const form = document.querySelector(FORM_SELECTOR);
    if (!form) return;

    wireLiveField(form, "email", getEmailError);
    wireLiveField(form, "password", getPasswordRequired);

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitLogin(form);
    });

    form.addEventListener("input", () => clearError(form));

    form.addEventListener("click", (event) => {
        const btn = event.target.closest(".admin-login__toggle");
        if (btn) togglePasswordVisibility(btn);
    });
}

async function submitLogin(form) {
    const emailInput = form.querySelector('input[name="email"]');
    const passwordInput = form.querySelector('input[name="password"]');
    const submitBtn = form.querySelector('[type="submit"]');
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    const remember = form.querySelector('input[name="remember"]').checked;

    clearError(form);

    const emailError = getEmailError(email);
    if (emailError) {
        showFieldError(form, emailInput, "email", emailError);
        return;
    }

    const passwordError = getPasswordRequired(password);
    if (passwordError) {
        showFieldError(form, passwordInput, "password", passwordError);
        return;
    }

    submitBtn.disabled = true;
    form.setAttribute("aria-busy", "true");
    let redirecting = false;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(form),
            },
            body: JSON.stringify({ email, password, remember }),
        });

        const data = await response.json().catch(() => ({}));

        if (response.ok) {
            redirecting = true;
            window.location.assign(
                data.redirect || form.dataset.redirect || window.location.href,
            );
            return;
        }

        if (response.status === 403) {
            showError(form, data.message || MESSAGES.noAccess);
        } else if (response.status === 429) {
            showError(form, data.message || MESSAGES.throttled);
        } else if (response.status === 422) {
            const errors = data.errors || {};
            const fieldErrors = [
                [emailInput, "email"],
                [passwordInput, "password"],
            ].filter(([, name]) => errors[name]?.length);

            fieldErrors.forEach(([input, name]) =>
                showFieldError(form, input, name, errors[name][0]),
            );

            // A combined message keeps "which one is wrong" vague on purpose.
            if (!fieldErrors.length) {
                showError(form, data.message || MESSAGES.credentials, [
                    emailInput,
                    passwordInput,
                ]);
            }
        } else {
            showError(form, data.message || MESSAGES.network);
        }
    } catch {
        showError(form, MESSAGES.network);
    } finally {
        if (!redirecting) {
            submitBtn.disabled = false;
            form.removeAttribute("aria-busy");
        }
    }
}

function showFieldError(form, input, name, message) {
    setFieldState(
        input,
        form.querySelector(`[data-field-error="${name}"]`),
        message,
    );
    input.focus();
}

function showError(form, message, invalidInputs = []) {
    const errorEl = form.querySelector("[data-login-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
    invalidInputs.forEach((input) => input.classList.add("is-invalid"));
}

function clearError(form) {
    const errorEl = form.querySelector("[data-login-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    clearInvalidWithoutFieldError(form);
}

document.addEventListener("DOMContentLoaded", initAdminLogin);

export { initAdminLogin };
