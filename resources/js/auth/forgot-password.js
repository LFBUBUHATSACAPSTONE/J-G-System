/**
 * Forgot Password submit (AJAX) + live field feedback
 *
 * Intercepts the submit, validates the identifier live with a message
 * naming what's wrong, posts via fetch, and on success moves the modal to
 * the verification view.
 *
 * Expected back-end contract for `password.email`:
 *   200 JSON (optional {"destination": "j***@mail.com"}) -> code sent
 *   422 {"message": "…"} -> failed
 * If `destination` is omitted, a masked version of the typed value is shown.
 */

import { setAuthView, focusFirstField } from "../auth-modal.js";
import {
    setVerificationDestination,
    setVerificationToken,
} from "./verification-code.js";
import { getIdentifierError, wireLiveField } from "./validation.js";

const AUTH_MODAL_ID = "authModal";
const VIEW_SELECTOR = '[data-view="forgot-password"]';

function initForgotPassword() {
    const modalEl = document.getElementById(AUTH_MODAL_ID);
    if (!modalEl) return;

    const form = modalEl.querySelector(`${VIEW_SELECTOR} form`);
    if (form) {
        wireLiveField(form, "identifier", getIdentifierError);
    }

    modalEl.addEventListener("submit", (event) => {
        const target = event.target;
        if (!target.closest(VIEW_SELECTOR)) return;
        event.preventDefault();
        submitForgotPassword(modalEl, target);
    });

    modalEl.addEventListener("input", (event) => {
        const target = event.target.closest("form");
        if (target?.closest(VIEW_SELECTOR)) clearError(target);
    });

    modalEl.addEventListener("hidden.bs.modal", () => {
        const target = modalEl.querySelector(`${VIEW_SELECTOR} form`);
        if (target) clearError(target);
    });
}

async function submitForgotPassword(modalEl, form) {
    const input = form.querySelector('input[name="identifier"]');
    const identifier = input.value.trim();
    const submitBtn = form.querySelector('[type="submit"]');

    clearError(form);

    const identifierError = getIdentifierError(identifier);
    if (identifierError) {
        showError(form, identifierError);
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
                    "We couldn't find an account with that email or phone number.",
            );
            return;
        }

        const data = await response.json().catch(() => ({}));
        setVerificationToken(data.verification_token);
        setVerificationDestination(
            data.destination || maskIdentifier(identifier),
        );
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
