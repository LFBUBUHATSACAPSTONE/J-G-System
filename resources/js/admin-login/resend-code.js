/**
 * Admin Resend Code (AJAX). Same as auth/resend-code.js: listens for the bubbling
 * `auth:resend-code` event from verification-code.js (which also starts the cooldown) and calls the
 * URL in the button's `data-resend-url`.
 *
 * Back-end contract for `admin.verification.resend` (POST JSON {context, verification_token}):
 *   200 {destination?, verification_token?}  -> new code sent
 *   422 / 429 {message}                      -> failed / throttled
 */

import { getCsrfToken } from "../auth/csrf.js";
import {
    setVerificationDestination,
    setVerificationToken,
} from "./verification-code.js";

export function initResendCode(root) {
    root.addEventListener("auth:resend-code", (event) => {
        resendCode(
            event.target,
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
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            showError(
                errorEl,
                data.message || "Could not resend the code. Please try again.",
            );
            return;
        }

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
