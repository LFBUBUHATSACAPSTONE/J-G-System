/**
 * Resend Code (AJAX)
 *
 * Listens for the bubbling `auth:resend-code` event dispatched by
 * verification-code.js (which also starts the 30 s cooldown) and calls
 * the resend endpoint read from the button's `data-resend-url`.
 *
 * Expected back-end contract for `verification.resend`:
 *   request: { context: "reset" | "signup", verification_token: "…" }
 *   200 JSON {"destination": "j***@mail.com", "verification_token": "…"} -> new code sent
 *   422 / 429 {"message": "…"} -> failed / throttled
 */

import { getCsrfToken } from "./csrf.js";
import {
    setVerificationDestination,
    setVerificationToken,
} from "./verification-code.js";

const AUTH_MODAL_ID = "authModal";

function initResendCode() {
    const modalEl = document.getElementById(AUTH_MODAL_ID);
    if (!modalEl) return;

    modalEl.addEventListener("auth:resend-code", (event) => {
        const btn = event.target;
        resendCode(
            btn,
            event.detail?.context ?? "reset",
            event.detail?.verificationToken ?? "",
        );
    });
}

async function resendCode(btn, context, verificationToken) {
    const form = btn.closest("form");
    const errorEl = form.querySelector("[data-verification-error]");

    try {
        const response = await fetch(btn.dataset.resendUrl, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(form),
            },
            body: JSON.stringify({
                context,
                verification_token: verificationToken,
            }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            showError(
                errorEl,
                data.message || "Could not resend the code. Please try again.",
            );
            return;
        }

        const data = await response.json().catch(() => ({}));
        if (data.destination) setVerificationDestination(data.destination);
        if (data.verification_token)
            setVerificationToken(data.verification_token);
    } catch {
        showError(errorEl, "Something went wrong. Please try again.");
    }
}

function showError(errorEl, message) {
    if (!errorEl) return;
    errorEl.textContent = message;
    errorEl.classList.remove("d-none");
}

document.addEventListener("DOMContentLoaded", initResendCode);

export { initResendCode };