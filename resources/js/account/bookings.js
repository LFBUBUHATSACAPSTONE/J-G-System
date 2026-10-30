/**
 * Account > My Bookings: tab filter, details modal and cancellation request.
 * FRONT END ONLY. Every booking comes from the server-rendered cards (their data-booking JSON); this
 * file never decides a status, it shows what the server answers. Same pattern as profile.js.
 *
 * Back-end contract for `user.account.bookings.cancel` (POST, JSON body {"reason": "…"}, {booking} = id):
 *   200 {"ok": true, "message": "…", "booking": {"id": "…", "status": "pending_cancellation", "cancellation": {…}}}
 *   401 / 403 / 409 {"message": "…"}  -> shown inside the cancel modal
 *   422 {"message": "…", "errors": {"reason": ["…"]}}  -> the reason message is shown under the reason box
 * The request does NOT cancel the booking: it moves to `pending_cancellation` and waits for an admin.
 * The cancellation object carries the user's reason and, later, the admin's decision/reason, so View
 * details can show pending / approved / declined without a reload. Generic booking declines still use
 * `reason` from `status_reason`; reschedule links point at `user.booking?reschedule=<id>`.
 */

import { getCsrfToken } from "../auth/csrf.js";
import { openActionModal, reasonError } from "../shared/action-modal.js";

const ICON_CLASS = "ph ph-";

function formatDateTime(value) {
    if (!value) return "";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
    });
}

function initBookings() {
    const root = document.querySelector("[data-account-bookings]");
    if (!root) return;

    const cancelUrlTemplate = root.dataset.cancelUrlTemplate || "#";
    const rescheduleUrl = root.dataset.rescheduleUrl || "#";

    const cards = () => [...root.querySelectorAll("[data-booking-card]")];
    const tabs = [...root.querySelectorAll("[data-bookings-tab]")];
    const readCard = (card) => JSON.parse(card.dataset.booking);

    let activeTab =
        tabs.find((tab) => tab.classList.contains("is-active"))?.dataset
            .bookingsTab ?? "all";

    // Tabs
    function applyFilter() {
        let visible = 0;
        cards().forEach((card) => {
            const show =
                activeTab === "all" || card.dataset.group === activeTab;
            card.hidden = !show;
            if (show) visible += 1;
        });

        root.querySelectorAll("[data-bookings-empty]").forEach((el) => {
            el.classList.toggle(
                "d-none",
                !(visible === 0 && el.dataset.bookingsEmpty === activeTab),
            );
        });

        tabs.forEach((tab) => {
            const on = tab.dataset.bookingsTab === activeTab;
            tab.classList.toggle("is-active", on);
            tab.setAttribute("aria-pressed", String(on));
        });
    }

    tabs.forEach((tab) =>
        tab.addEventListener("click", () => {
            activeTab = tab.dataset.bookingsTab;
            applyFilter();
        }),
    );

    // Badge (card and modal share the same markup)
    function paintBadge(el, { label, icon, tone }) {
        el.className = `account-badge account-badge--${tone}`;
        el.textContent = "";
        const i = document.createElement("i");
        i.className = `${ICON_CLASS}${icon}`;
        i.setAttribute("aria-hidden", "true");
        el.append(i, document.createTextNode(label));
    }

    // Details modal
    const detailsModal = document.getElementById("bookingDetailsModal");

    // The client's request and J&G's answer, in one block. The user's reason is always shown;
    // the admin block is added only when there is an admin decision or a direct cancellation.
    function renderCancellation(data) {
        if (!detailsModal) return;

        const box = detailsModal.querySelector("[data-bk-cancellation]");
        if (!box) return;

        const cancellation = data.cancellation;
        const hasReason = Boolean(
            cancellation?.user_reason || cancellation?.admin_reason,
        );
        const show =
            Boolean(cancellation) &&
            (cancellation.status === "pending" || hasReason);

        box.hidden = !show;
        if (!show) return;

        const title = box.querySelector("[data-bk-cancellation-title]");
        const meta = box.querySelector("[data-bk-cancellation-meta]");
        const userBlock = box.querySelector("[data-bk-cancellation-user]");
        const userReason = box.querySelector(
            "[data-bk-cancellation-user-reason]",
        );
        const userDate = box.querySelector("[data-bk-cancellation-user-date]");
        const adminBlock = box.querySelector("[data-bk-cancellation-admin]");
        const adminLabel = box.querySelector(
            "[data-bk-cancellation-admin-label]",
        );
        const adminReason = box.querySelector(
            "[data-bk-cancellation-admin-reason]",
        );
        const adminDate = box.querySelector("[data-bk-cancellation-admin-date]");

        const when = formatDateTime(cancellation.admin_cancelled_at);
        const requested = formatDateTime(cancellation.user_cancelled_at);

        switch (cancellation.status) {
            case "pending":
                title.textContent = "Cancellation request pending";
                meta.textContent =
                    "Waiting for J&G to review your request. The booking stays active for now.";
                break;
            case "approved":
                title.textContent = "Cancellation approved";
                meta.textContent = when
                    ? `J&G approved your request on ${when}. The booking is cancelled.`
                    : "J&G approved your request. The booking is cancelled.";
                break;
            case "declined":
                title.textContent = "Cancellation request declined";
                meta.textContent = when
                    ? `J&G declined your request on ${when}. The booking stays active.`
                    : "J&G declined your request. The booking stays active.";
                break;
            case "cancelled_by_admin":
                title.textContent = "Cancelled by J&G";
                meta.textContent = when
                    ? `J&G cancelled this booking on ${when}.`
                    : "J&G cancelled this booking.";
                break;
            case "user_cancelled":
                title.textContent = "Cancelled by you";
                meta.textContent = "";
                break;
            default:
                title.textContent = "Cancelled";
                meta.textContent = "";
        }

        userBlock.hidden = !cancellation.user_reason;
        if (cancellation.user_reason) {
            userReason.textContent = cancellation.user_reason;
            userDate.textContent = requested ? `Submitted ${requested}` : "";
            userDate.hidden = !requested;
        }

        // The admin's reason is shown only when J&G is the one who ended the booking: a decline
        // (the request was refused) or a direct cancellation. On an approved request the client's
        // own reason is the reason; an optional approval note is not shown here.
        const showAdminReason =
            cancellation.status === "declined" ||
            cancellation.status === "cancelled_by_admin";

        adminBlock.hidden = !(showAdminReason && cancellation.admin_reason);
        if (showAdminReason && cancellation.admin_reason) {
            adminLabel.textContent =
                cancellation.status === "declined"
                    ? "J&G's reason for declining"
                    : "J&G's reason for cancelling";
            adminReason.textContent = cancellation.admin_reason;
            adminDate.textContent = when;
            adminDate.hidden = !when;
        }
    }

    detailsModal?.addEventListener("show.bs.modal", (event) => {
        const card = event.relatedTarget?.closest("[data-booking-card]");
        if (!card) return;
        const data = readCard(card);

        detailsModal.querySelectorAll("[data-bk]").forEach((el) => {
            const value = data[el.dataset.bk];
            el.textContent =
                value === null || value === undefined || value === ""
                    ? "--"
                    : value;
        });
        paintBadge(detailsModal.querySelector("[data-bk-badge]"), data);

        // Why it was cancelled or declined (generic booking decline / legacy cancellation).
        const reasonBox = detailsModal.querySelector("[data-bk-reason]");
        reasonBox.hidden = !data.reason;
        if (data.reason) {
            reasonBox.querySelector("[data-bk-reason-label]").textContent =
                data.reason_label || "Reason";
            reasonBox.querySelector("[data-bk-reason-by]").textContent =
                data.reason_by ? `Given by ${data.reason_by}` : "";
            reasonBox.querySelector("[data-bk-reason-text]").textContent =
                data.reason;
        }

        // The cancellation request lifecycle (pending / approved / declined / direct admin).
        renderCancellation(data);
    });

    // Cancel modal: the shared action modal, with a required reason. This sends a REQUEST; it
    // never changes the card to "cancelled" on its own.
    const cancelModalEl = document.getElementById("cancelBookingModal");

    // The card's Cancel booking button opens the request modal (it no longer uses data-bs-toggle).
    root.addEventListener("click", async (event) => {
        const button = event.target.closest("[data-booking-cancel]");
        if (!button || !cancelModalEl) return;
        const card = button.closest("[data-booking-card]");
        if (!card) return;
        const { id, reference, event: eventName } = readCard(card);

        await openActionModal(cancelModalEl, {
            title: "Request a cancellation?",
            message: `You are about to ask J&G to cancel ${eventName} (${reference}). The booking stays active until an admin reviews your request.`,
            confirmLabel: "Send request",
            tone: "danger",
            reason: true,
            reasonLabel: "Reason for cancellation",
            reasonHint:
                "Tell J&G why you want to cancel. This is saved in your booking details.",
            onConfirm: async (reason) => {
                let response;
                let data;
                try {
                    response = await fetch(
                        cancelUrlTemplate.replace(
                            "__ID__",
                            encodeURIComponent(id),
                        ),
                        {
                            method: "POST",
                            headers: {
                                Accept: "application/json",
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": getCsrfToken(),
                            },
                            body: JSON.stringify({ reason }),
                        },
                    );
                    data = await response.json().catch(() => ({}));
                } catch {
                    throw new Error(
                        "Something went wrong on our end. Please check your connection and try again.",
                    );
                }

                if (!response.ok) {
                    const reasonMessage = data.errors?.reason?.[0];
                    if (reasonMessage) throw reasonError(reasonMessage);
                    throw new Error(
                        data.message ||
                            "We couldn't send your cancellation request. Please try again.",
                    );
                }

                // The server has saved the request. Reload My Bookings so its rendered status
                // reflects the pending request and the cancellation reason form is closed.
                window.location.reload();
            },
        });
    });

    applyFilter();
}

document.addEventListener("DOMContentLoaded", initBookings);
