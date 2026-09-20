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

import { setAuthView, focusFirstField } from "../auth-modal.js";
import { maskIdentifier } from "./forgot-password.js";
import {
    setVerificationContext,
    setVerificationDestination,
} from "./verification-code.js";
import {
    getIdentifierError,
    getPasswordError,
    getRequiredError,
    wireLiveField,
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
        wireLiveField(form, "first_name", (v) =>
            getRequiredError(v, "First name"),
        );
        wireLiveField(form, "last_name", (v) =>
            getRequiredError(v, "Last name"),
        );
        wireLiveField(form, "identifier", getIdentifierError);
        wireLiveField(form, "password", getPasswordError);

        // Checkboxes don't fit wireLiveField's blur/input pattern (there's
        // nothing to "type"), so this listens to 'change' directly. Only
        // clears the error once checked — unchecking after already having
        // agreed re-flags it immediately, same as any other live field.
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
        [firstNameInput, getRequiredError(firstNameInput.value, "First name")],
        [lastNameInput, getRequiredError(lastNameInput.value, "Last name")],
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
        showError(form, message, []);
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
                "X-CSRF-TOKEN":
                    form.querySelector('input[name="_token"]')?.value ?? "",
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
    form.querySelectorAll("input").forEach((input) =>
        input.classList.remove("is-invalid"),
    );
}

function resetForm(form) {
    form.reset();
    clearError(form);
}

document.addEventListener("DOMContentLoaded", initSignup);

export { initSignup };
