/**
 * Sign Up submit (AJAX) + live field feedback
 *
 * Intercepts the sign-up form, validates each field live with a message
 * naming exactly what's missing, posts JSON, and on success opens the
 * Verification view. The form has `novalidate`, so every required field including the Terms checkbox — is enforced here in JS, not by the browser.
 *
 * Expected back-end contract for `register`:
 *   200 JSON (optional {"destination": "j***@mail.com"}) -> account created, code sent
 *   422 {"message": "…", "errors": {"identifier": ["…"], …}} -> rejected
 */

import { getCsrfToken } from "./csrf.js";
import { setAuthView, focusFirstField } from "../auth-modal.js";
import { maskIdentifier } from "./forgot-password.js";
import {
    setVerificationContext,
    setVerificationDestination,
    setVerificationToken,
} from "./verification-code.js";
import {
    getIdentifierError,
    getPasswordError,
    getNameError,
    wireLiveFieldImmediate,
    clearInvalidWithoutFieldError,
    clearAllFieldStates,
    setFieldState,
} from "./validation.js";

const TERMS_MESSAGE = "Please agree to the Terms & Conditions to continue.";

const AUTH_MODAL_ID = "authModal";
const VIEW_SELECTOR = '[data-view="signup"]';

function initSignup() {
    const modalEl = document.getElementById(AUTH_MODAL_ID);
    if (!modalEl) return;

    const form = modalEl.querySelector(`${VIEW_SELECTOR} form`);
    if (form) {
        // The letters-only filter is registered BEFORE the live validators so
        // each validator sees the already-filtered value (otherwise a typed
        // "1" would flash an error for text that is about to be removed).
        initNameFilter(form, "first_name");
        initNameFilter(form, "last_name");
        wireLiveFieldImmediate(form, "first_name", (v) =>
            getNameError(v, "First name"),
        );
        wireLiveFieldImmediate(form, "last_name", (v) =>
            getNameError(v, "Last name"),
        );
        // Email and password validate on every keystroke (not on blur).
        wireLiveFieldImmediate(form, "identifier", getIdentifierError);
        wireLiveFieldImmediate(form, "password", getPasswordError);

        /* Checkboxes don't fit wireLiveFieldImmediate's 'input' pattern (there's nothing to "type"), so this listens to 'change' directly. Only clears the error once checked — unchecking after already having agreed re-flags it immediately, same as any other live field.
        */
        const termsInput = form.querySelector('[name="terms"]');
        const termsError = form.querySelector('[data-field-error="terms"]');
        termsInput?.addEventListener("change", () => {
            setFieldState(
                termsInput,
                termsError,
                termsInput.checked ? "" : TERMS_MESSAGE,
            );
        });
    }

    modalEl.addEventListener("submit", (event) => {
        const target = event.target;
        if (!target.closest(VIEW_SELECTOR)) return;
        event.preventDefault();
        submitSignup(modalEl, target);
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

/*
 Blocks digits/symbols from ever being typed into First/Last Name — same
 "filter on input" approach as the booking flow's Client Information step
 (initNameFilter in booking/client-information.js). getNameError still runs
 via wireLiveFieldImmediate and on submit as a backstop (e.g. for pasted text).
*/
function initNameFilter(form, fieldName) {
    const input = form.querySelector(`[name="${fieldName}"]`);
    input?.addEventListener("input", () => {
        input.value = input.value.replace(/[^A-Za-z\s]/g, "");
    });
}

async function submitSignup(modalEl, form) {
    const field = (name) => form.querySelector(`[name="${name}"]`);
    const firstNameInput = field("first_name");
    const lastNameInput = field("last_name");
    const identifierInput = field("identifier");
    const passwordInput = field("password");
    const termsInput = field("terms");
    const submitBtn = form.querySelector('[type="submit"]');

    clearError(form);

    const checks = [
        [firstNameInput, getNameError(firstNameInput.value, "First name")],
        [lastNameInput, getNameError(lastNameInput.value, "Last name")],
        [identifierInput, getIdentifierError(identifierInput.value)],
        [passwordInput, getPasswordError(passwordInput.value)],
        [termsInput, termsInput.checked ? "" : TERMS_MESSAGE],
    ];
    const firstFailure = checks.find(([, message]) => message);
    if (firstFailure) {
        const [input, message] = firstFailure;
        const errorEl = form.querySelector(
            `[data-field-error="${input.name}"]`,
        );
        setFieldState(input, errorEl, message);
        input.focus();
        return;
    }

    const identifier = identifierInput.value.trim();
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
                first_name: firstNameInput.value.trim(),
                last_name: lastNameInput.value.trim(),
                identifier,
                password: passwordInput.value,
                terms: field("terms").checked,
            }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            const errors = data.errors ?? {};
            const invalidInputs = Object.keys(errors)
                .map((name) => form.querySelector(`[name="${name}"]`))
                .filter(Boolean);
            showError(
                form,
                Object.values(errors)[0]?.[0] ||
                    data.message ||
                    "We couldn't create your account. Please review the fields above and try again.",
                invalidInputs,
            );
            return;
        }

        const data = await response.json().catch(() => ({}));
        setVerificationContext("signup");
        setVerificationToken(data.verification_token);
        setVerificationDestination(
            data.destination || maskIdentifier(identifier),
        );
        resetForm(form);
        setAuthView(modalEl, "verification");
        focusFirstField(modalEl);
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
    const errorEl = form.querySelector("[data-signup-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
    invalidInputs.forEach((input) => input.classList.add("is-invalid"));
}

function clearError(form) {
    const errorEl = form.querySelector("[data-signup-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    clearInvalidWithoutFieldError(form);
}

function resetForm(form) {
    form.reset();
    clearError(form);
    clearAllFieldStates(form);
}

document.addEventListener("DOMContentLoaded", initSignup);

export { initSignup };