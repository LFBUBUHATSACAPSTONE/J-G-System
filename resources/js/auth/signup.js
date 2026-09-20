// resources/js/signup.js

/**
 * Sign Up submit (AJAX)
 *
 * Intercepts the sign-up form, validates the identifier client-side,
 * posts JSON, and on success opens the Verification view.
 * Required fields and the Terms checkbox are enforced by native
 * `required` attributes before this handler runs.
 *
 * Expected back-end contract for `register`:
 *   200 JSON (optional {"destination": "j***@mail.com"}) -> account created, code sent
 *   422 {"message": "…", "errors": {"identifier": ["…"], …}} -> rejected
 */

import { setAuthView } from "../auth-modal.js";
import { maskIdentifier } from "./forgot-password.js";
import {
    setVerificationContext,
    setVerificationDestination,
} from "./verification-code.js";

const AUTH_MODAL_ID = "authModal";
const VIEW_SELECTOR = '[data-view="signup"]';
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PHONE_RE = /^\+?[\d\s()-]+$/;

function initSignup() {
    const modalEl = document.getElementById(AUTH_MODAL_ID);
    if (!modalEl) return;

    modalEl.addEventListener("submit", (event) => {
        const form = event.target;
        if (!form.closest(VIEW_SELECTOR)) return;
        event.preventDefault();
        submitSignup(modalEl, form);
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

function isValidIdentifier(value) {
    if (EMAIL_RE.test(value)) return true;
    const digits = value.replace(/\D/g, "");
    return PHONE_RE.test(value) && digits.length >= 7 && digits.length <= 15;
}

async function submitSignup(modalEl, form) {
    const field = (name) => form.querySelector(`[name="${name}"]`);
    const identifierInput = field("identifier");
    const identifier = identifierInput.value.trim();
    const submitBtn = form.querySelector('[type="submit"]');

    clearError(form);

    if (!isValidIdentifier(identifier)) {
        showError(form, "Enter a valid email address or phone number.", [
            "identifier",
        ]);
        identifierInput.focus();
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
                first_name: field("first_name").value.trim(),
                last_name: field("last_name").value.trim(),
                identifier,
                password: field("password").value,
                terms: field("terms").checked,
            }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            const errors = data.errors ?? {};
            showError(
                form,
                Object.values(errors)[0]?.[0] ||
                    data.message ||
                    "Could not create your account. Please try again.",
                Object.keys(errors),
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
        modalEl
            .querySelector('[data-view="verification"] .auth-modal__code-box')
            ?.focus();
    } catch {
        showError(form, "Something went wrong. Please try again.");
    } finally {
        submitBtn.disabled = false;
    }
}

function showError(form, message, invalidNames = []) {
    const errorEl = form.querySelector("[data-signup-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
    invalidNames.forEach((name) =>
        form.querySelector(`[name="${name}"]`)?.classList.add("is-invalid"),
    );
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

export { initSignup, isValidIdentifier };
