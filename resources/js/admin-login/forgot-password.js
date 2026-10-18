/**
 * Admin Forgot Password (AJAX) + live field feedback.
 * Same flow as auth/forgot-password.js: validate the email on every keystroke, POST, then move to
 * the verification view.
 *
 * Back-end contract for `admin.password.email` (POST JSON {email}):
 *   200 {verification_token, destination?}  -> code sent ("destination" e.g. "a***@mail.com";
 *                                              if omitted a masked version of the typed email shows)
 *   422 {message}                           -> no such admin / failed
 *   429 {message}                           -> too many attempts
 */

import { getCsrfToken } from "../auth/csrf.js";
import {
    getIdentifierError,
    wireLiveFieldImmediate,
    clearInvalidWithoutFieldError,
    setFieldState,
} from "../auth/validation.js";
import { setView, focusFirstField } from "./views.js";
import {
    setVerificationDestination,
    setVerificationToken,
} from "./verification-code.js";

const FORM_SELECTOR = "[data-admin-forgot]";

export function initForgotPassword(root) {
    const form = root.querySelector(FORM_SELECTOR);
    if (!form) return;

    wireLiveFieldImmediate(form, "email", getIdentifierError);

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitForgotPassword(root, form);
    });
    form.addEventListener("input", () => clearError(form));
}

async function submitForgotPassword(root, form) {
    const input = form.querySelector('input[name="email"]');
    const email = input.value.trim();
    const submitBtn = form.querySelector('[type="submit"]');

    clearError(form);

    const emailError = getIdentifierError(email);
    if (emailError) {
        setFieldState(
            input,
            form.querySelector('[data-field-error="email"]'),
            emailError,
        );
        input.focus();
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
            body: JSON.stringify({ email }),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            showError(
                form,
                data.message ||
                    (response.status === 429
                        ? "Too many attempts. Please wait a moment and try again."
                        : "We couldn't find an account with that email address."),
            );
            return;
        }

        setVerificationToken(data.verification_token, email);
        setVerificationDestination(data.destination || maskEmail(email));
        setView(root, "verification");
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

export function maskEmail(value) {
    const [local = "", domain = ""] = value.split("@");
    return `${local[0] ?? ""}***@${domain}`;
}

function showError(form, message) {
    const errorEl = form.querySelector("[data-forgot-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
    form.querySelector('input[name="email"]').classList.add("is-invalid");
}

function clearError(form) {
    const errorEl = form.querySelector("[data-forgot-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    clearInvalidWithoutFieldError(form);
}
