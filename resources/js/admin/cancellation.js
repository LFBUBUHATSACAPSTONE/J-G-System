/**
 * Admin cancellation review.
 *
 * The two buttons live in View Details (components/admin/booking-modal.blade.php), next to the
 * client's reason. Approving cancels the booking; declining restores the status it had before the
 * request. Both go through the same confirmation modal as every other admin booking action.
 *
 * Back-end contract (`admin.bookings.cancellation.review`, POST JSON):
 *   200 {ok: true, message, booking}     review saved
 *   404 / 409 {message}                  gone / no pending request any more
 *   422 {message, errors{reason}}        the reason is missing or outside config/cancellation.php
 *
 * FRONT END ONLY: the server decides every status. On success the page reloads after a short
 * message, so the row badge, filter group and actions all reflect the new state.
 */

import { openActionModal, reasonError } from "../shared/action-modal.js";
import { getCsrfToken } from "../auth/csrf.js";

const TITLES = {
    pending: "Cancellation requested",
    approved: "Cancellation approved",
    declined: "Cancellation request declined",
    cancelled_by_admin: "Cancelled by J&G",
    cancelled: "Cancelled",
    user_cancelled: "Cancelled",
};

function formatDateTime(value) {
    if (!value) return "";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
    });
}

function metaFor(cancellation) {
    const when = formatDateTime(cancellation.admin_cancelled_at);

    switch (cancellation.status) {
        case "pending":
            return "Waiting for an admin decision. The booking keeps its slot.";
        case "approved":
            return when
                ? `Approved on ${when}. The booking is cancelled.`
                : "Approved. The booking is cancelled.";
        case "declined":
            return when
                ? `Declined on ${when}. The booking stays active.`
                : "Declined. The booking stays active.";
        case "cancelled_by_admin":
            return when
                ? `J&G cancelled this booking directly on ${when}.`
                : "J&G cancelled this booking directly.";
        default:
            return "";
    }
}

export function initCancellation(modal) {
    const section = modal.querySelector("[data-cancellation-section]");
    if (!section) return;

    const title = section.querySelector("[data-cancellation-title]");
    const meta = section.querySelector("[data-cancellation-meta]");
    const userBlock = section.querySelector("[data-cancellation-user]");
    const userReason = section.querySelector("[data-cancellation-user-reason]");
    const userDate = section.querySelector("[data-cancellation-user-date]");
    const adminBlock = section.querySelector("[data-cancellation-admin]");
    const adminLabel = section.querySelector("[data-cancellation-admin-label]");
    const adminReason = section.querySelector(
        "[data-cancellation-admin-reason]",
    );
    const adminDate = section.querySelector("[data-cancellation-admin-date]");
    const actions = section.querySelector("[data-cancellation-actions]");
    const approveButton = section.querySelector("[data-approve-cancellation]");
    const declineButton = section.querySelector("[data-decline-cancellation]");

    const actionModal = document.getElementById("bookingActionModal");
    let current = null;

    function render(booking) {
        const cancellation = booking?.cancellation;
        const hasReason = Boolean(
            cancellation?.user_reason || cancellation?.admin_reason,
        );
        const show =
            Boolean(cancellation) &&
            (cancellation.status === "pending" || hasReason);

        section.hidden = !show;
        if (!show) return;

        title.textContent = TITLES[cancellation.status] || "Cancellation";
        meta.textContent = metaFor(cancellation);

        userBlock.hidden = !cancellation.user_reason;
        if (cancellation.user_reason) {
            userReason.textContent = cancellation.user_reason;
            const requested = formatDateTime(cancellation.user_cancelled_at);
            userDate.textContent = requested ? `Requested ${requested}` : "";
            userDate.hidden = !requested;
        }

        adminBlock.hidden = !cancellation.admin_reason;
        if (cancellation.admin_reason) {
            adminLabel.textContent =
                cancellation.status === "declined"
                    ? "J&G's reason for declining"
                    : cancellation.status === "approved"
                      ? "Note from J&G"
                      : "J&G's reason for cancelling";
            adminReason.textContent = cancellation.admin_reason;
            const decided = formatDateTime(cancellation.admin_cancelled_at);
            adminDate.textContent = decided;
            adminDate.hidden = !decided;
        }

        // Only a request that is still waiting can be answered. Approved / declined / direct
        // cancellations are history: the modal shows them, but there is nothing left to press.
        actions.hidden = cancellation.status !== "pending";
    }

    modal.addEventListener("show.bs.modal", (event) => {
        const trigger = event.relatedTarget;
        if (!trigger?.dataset.booking) return;
        current = JSON.parse(trigger.dataset.booking);
        render(current);
    });

    modal.addEventListener("hidden.bs.modal", () => {
        current = null;
    });

    function showFeedback(message) {
        const feedback = document.querySelector("[data-bookings-feedback]");
        if (!feedback) return;
        feedback.textContent = message;
        delete feedback.dataset.tone;
        feedback.hidden = false;
        feedback.focus?.();
    }

    async function review(action, reason) {
        if (!current) throw new Error("No booking selected.");

        const url = String(modal.dataset.reviewUrlTemplate || "").replace(
            "__ID__",
            encodeURIComponent(current.id),
        );
        if (!url || url === "#") {
            throw new Error("The cancellation review URL is missing.");
        }

        let response;
        let data;
        try {
            response = await fetch(url, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(),
                },
                body: JSON.stringify(reason ? { action, reason } : { action }),
            });
            data = await response.json().catch(() => ({}));
        } catch {
            throw new Error(
                "Something went wrong on our end. Please check your connection and try again.",
            );
        }

        if (!response.ok) {
            const reasonMessage = data.errors?.reason?.[0];
            const error = reasonError(
                reasonMessage ||
                    data.message ||
                    "The cancellation request could not be processed.",
            );
            if (!reasonMessage) delete error.field;
            throw error;
        }

        showFeedback(
            data.message || "The cancellation request has been updated.",
        );
        window.setTimeout(() => window.location.reload(), 700);
    }

    approveButton?.addEventListener("click", () => {
        if (!current || !actionModal) return;

        openActionModal(actionModal, {
            title: "Approve this cancellation?",
            message: `Booking ${current.reference} will be cancelled. The client's reason stays in the booking details.`,
            confirmLabel: "Approve cancellation",
            tone: "danger",
            onConfirm: () => review("approve"),
        });
    });

    declineButton?.addEventListener("click", () => {
        if (!current || !actionModal) return;

        openActionModal(actionModal, {
            title: "Decline this cancellation request?",
            message: `Booking ${current.reference} will stay active. The client will see your reason in their booking details.`,
            confirmLabel: "Decline request",
            tone: "brand",
            reason: true,
            reasonLabel: "Reason for declining",
            reasonHint:
                "Shown to the client. Tell them why the cancellation cannot be approved.",
            onConfirm: (reason) => review("reject", reason),
        });
    });
}
