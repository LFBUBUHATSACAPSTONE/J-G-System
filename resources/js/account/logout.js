/**
 * Account > Log out (button at the bottom of the sidebar, every account page).
 * The `logout` route answers JSON, so the form is sent with fetch() and the page then goes to the
 * landing page.
 *
 * Back-end contract for `logout` (POST, CSRF header):
 *   200 {"ok": true, "message": "…", "redirect": "/user/landing"}  -> go to `redirect`
 *   401 / 419                                                       -> the session is already gone: go to the landing page too
 */

import { getCsrfToken } from "../auth/csrf.js";

function initLogout() {
    const form = document.querySelector("[data-account-logout]");
    if (!form) return;

    const button = form.querySelector("button[type=\"submit\"]");
    const fallbackUrl = form.dataset.redirectUrl || "/";

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
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

            if (response.ok || response.status === 401 || response.status === 419) {
                window.location.assign(data.redirect || fallbackUrl);
                return;
            }
        } catch {
            // Fall through: leave the button usable so the user can try again.
        }

        if (button) button.disabled = false;
    });
}

document.addEventListener("DOMContentLoaded", initLogout);