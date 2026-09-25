/**
 * Login submit (AJAX) + live field feedback
 *
 * Intercepts the login form, posts JSON, and on success reloads/redirects
 * into the logged-in state. Failures show inline under the fields.
 *
 * Expected back-end contract for `login`:
 *   200 JSON (optional {"redirect": "/dashboard"}) -> authenticated
 *   422 {"message": "…", "errors": {"identifier": ["…"]}} -> rejected
 * `remember` is sent as a boolean for Auth::attempt($credentials, $remember).
 */

import {
    getIdentifierError,
    getRequiredError,
    wireLiveField,
    setFieldState,
} from "./validation.js";

const AUTH_MODAL_ID = "authModal";
const VIEW_SELECTOR = '[data-view="login"]';

function initLogin() {
    const modalEl = document.getElementById(AUTH_MODAL_ID);
    if (!modalEl) return;

    const form = modalEl.querySelector(`${VIEW_SELECTOR} form`);
    if (form) {
        wireLiveField(form, "identifier", getIdentifierError);
        // No format rule on login, just a flag if it's left empty.
        wireLiveField(form, "password", (v) => getRequiredError(v, "Password"));
    }

    modalEl.addEventListener("submit", (event) => {
        const target = event.target;
        if (!target.closest(VIEW_SELECTOR)) return;
        event.preventDefault();
        submitLogin(target);
    });

    modalEl.addEventListener("input", (event) => {
        const target = event.target.closest("form");
        if (target?.closest(VIEW_SELECTOR)) clearError(target);
    });

    modalEl.addEventListener("hidden.bs.modal", () => {
        const target = modalEl.querySelector(`${VIEW_SELECTOR} form`);
        if (target) {
            target.reset();
            clearError(target);
        }
    });
}

async function submitLogin(form) {
    const identifierInput = form.querySelector('input[name="identifier"]');
    const passwordInput = form.querySelector('input[name="password"]');
    const identifier = identifierInput.value.trim();
    const password = passwordInput.value;
    const remember = form.querySelector('input[name="remember"]').checked;
    const submitBtn = form.querySelector('[type="submit"]');

    clearError(form);

    const identifierError = getIdentifierError(identifier);
    if (identifierError) {
        const errorEl = form.querySelector('[data-field-error="identifier"]');
        setFieldState(identifierInput, errorEl, identifierError);
        identifierInput.focus();
        return;
    }

    const passwordError = getRequiredError(password, "Password");
    if (passwordError) {
        const errorEl = form.querySelector('[data-field-error="password"]');
        setFieldState(passwordInput, errorEl, passwordError);
        passwordInput.focus();
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
            body: JSON.stringify({ identifier, password, remember }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            /* The stub/back end can't say *which* field is wrong for security reasons (avoids confirming whether the account exists), so this stays a combined message — but it's still specific about what to check rather than a bare "error".
            */
            showError(
                form,
                data.message ||
                    "We couldn't sign you in — check that your email/phone and password are correct.",
                [identifierInput, passwordInput],
            );
            return;
        }

        const data = await response.json().catch(() => ({}));
        window.location.assign(data.redirect || window.location.href);
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
    form.querySelectorAll("input").forEach((input) =>
        input.classList.remove("is-invalid"),
    );
}

document.addEventListener("DOMContentLoaded", initLogin);

export { initLogin };
