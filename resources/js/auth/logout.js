/**
 * Log out (user account sidebar, admin sidebar and the admin Account page).
 * Any <form data-logout> asks "Log out?" in a modal first, then posts with fetch() because the
 * `logout` route answers JSON (a plain form post would show that JSON as a page), then goes to
 * the next page.
 *
 * Back-end contract for `logout` (POST, CSRF header):
 *   200 {"ok": true, "message": "…", "redirect": "/user/landing"}  -> go to `redirect`
 *   401 / 419                                                       -> the session is already gone: go on too
 *
 * `data-redirect-url` is the page to use when the response has no `redirect`. Add
 * `data-redirect-first` (admin forms) to use it before the response's `redirect`, so an admin
 * ends on the admin sign-in page.
 */

import { getCsrfToken } from "./csrf.js";
import { confirmDialog } from "../chat/confirm-dialog.js";

function initLogout() {
    document.querySelectorAll("form[data-logout]").forEach((form) => {
        const button = form.querySelector('button[type="submit"]');
        const fallbackUrl = form.dataset.redirectUrl || "/";
        const urlFirst = form.hasAttribute("data-redirect-first");
        let busy = false;

        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            if (busy) return;

            const ok = await confirmDialog({
                title: "Log out?",
                text: "You will need to sign in again to continue.",
                confirmLabel: "Log out",
            });
            if (!ok) {
                button?.focus();
                return;
            }

            busy = true;
            if (button) button.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": getCsrfToken(form),
                    },
                    body: "{}",
                });
                const data = await response.json().catch(() => ({}));

                if (
                    response.ok ||
                    response.status === 401 ||
                    response.status === 419
                ) {
                    window.location.assign(
                        urlFirst ? fallbackUrl : data.redirect || fallbackUrl,
                    );
                    return;
                }
            } catch {
                // Fall through: leave the button usable so they can try again.
            }

            busy = false;
            if (button) button.disabled = false;
        });
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initLogout);
} else {
    initLogout();
}
