/**
 * Account > Delete Account: confirmation modal (opened from the sidebar tab on any account page).
 * FRONT END ONLY. The server decides whether the account can be deleted; this file only gates the
 * button behind the typed confirm word and shows what the server answers. Same pattern as bookings.js.
 *
 * Nothing is sent until the backend adds the route: while `data-delete-url` is empty (the
 * `user.account.destroy` route does not exist) the confirm button only shows a notice.
 *
 * Back-end contract for `user.account.destroy` (DELETE, empty JSON body, current user only):
 *   200 {"ok": true, "message": "…", "redirect": "/"}  -> logged out, go to `redirect` (or Home)
 *   401 / 403 / 409 / 422 {"message": "…"}               -> shown inside the modal
 *                          (409 for example: the account still has active bookings)
 */

import { Offcanvas } from "bootstrap";
import { getCsrfToken } from "../auth/csrf.js";

function initDeleteAccount() {
    const modalEl = document.querySelector("[data-delete-account]");
    if (!modalEl) return;

    const input = modalEl.querySelector("[data-delete-input]");
    const confirmBtn = modalEl.querySelector("[data-delete-confirm]");
    const messageEl = modalEl.querySelector("[data-delete-message]");

    const deleteUrl = modalEl.dataset.deleteUrl || "";
    const redirectUrl = modalEl.dataset.redirectUrl || "/";
    const confirmWord = modalEl.dataset.confirmWord || "DELETE";

    const matches = () => input.value.trim() === confirmWord;
    const syncButton = () => (confirmBtn.disabled = !matches());

    function showMessage(text, type = "error") {
        messageEl.textContent = text;
        messageEl.classList.toggle("is-error", type === "error");
        messageEl.classList.toggle("d-none", !text);
    }

    input.addEventListener("input", () => {
        showMessage("");
        syncButton();
    });

    // Enter in the field counts as pressing the button, but only once the word matches.
    input.addEventListener("keydown", (event) => {
        if (event.key !== "Enter") return;
        event.preventDefault();
        if (matches()) confirmBtn.click();
    });

    // On phones the sidebar is an off-canvas panel: close it so the modal is not stacked on top of it.
    modalEl.addEventListener("show.bs.modal", () => {
        const sidebar = document.getElementById("accountSidebar");
        if (sidebar) Offcanvas.getInstance(sidebar)?.hide();
    });

    // Every open starts clean, so a half-typed word or an old error never carries over.
    modalEl.addEventListener("hidden.bs.modal", () => {
        input.value = "";
        showMessage("");
        syncButton();
    });

    modalEl.addEventListener("shown.bs.modal", () => input.focus());

    confirmBtn.addEventListener("click", async () => {
        if (!matches()) return;
        showMessage("");

        if (!deleteUrl) {
            showMessage(
                "Account deletion isn't connected yet, so nothing was deleted.",
            );
            return;
        }

        confirmBtn.disabled = true;
        input.disabled = true;

        try {
            const response = await fetch(deleteUrl, {
                method: "DELETE",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(),
                },
                body: "{}",
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                showMessage(
                    data.message ||
                        "We couldn't delete your account. Please try again.",
                );
                input.disabled = false;
                syncButton();
                return;
            }

            // Leave the button locked: the account is gone, so there is nothing left to press.
            window.location.assign(data.redirect || redirectUrl);
        } catch {
            showMessage(
                "Something went wrong on our end. Please check your connection and try again.",
            );
            input.disabled = false;
            syncButton();
        }
    });
}

document.addEventListener("DOMContentLoaded", initDeleteAccount);
