/**
 * Account > My Bookings: tab filter, details modal and cancel request.
 * FRONT END ONLY. Every booking comes from the server-rendered cards (their data-booking JSON); this
 * file never decides a status, it shows what the server answers. Same pattern as profile.js.
 *
 * Back-end contract for `user.account.bookings.cancel` (POST, JSON body {"reason": "…"}, {booking} = id):
 *   200 {"ok": true, "message": "…", "booking": {"id": "…", "status": "user_cancelled", "can_reschedule": true|false}}
 *   401 / 403 / 409 {"message": "…"}  -> shown inside the cancel modal
 *   422 {"message": "…", "errors": {"reason": ["…"]}}  -> the reason message is shown under the reason box
 * The cancel modal is the shared action modal (js/shared/action-modal.js) with a required reason; the
 * reason is also stored on the card (data-booking.reason) so View details shows it right away.
 * Cards cancelled or declined earlier carry `reason` from `status_reason` on the booking array.
 * Reschedule links point at the existing `user.booking?reschedule=<id>` page (data-reschedule-url).
 */

import { Modal } from "bootstrap";
import { getCsrfToken } from "../auth/csrf.js";
import { openActionModal, reasonError } from "../shared/action-modal.js";

const ICON_CLASS = "ph ph-";

function initBookings() {
    const root = document.querySelector("[data-account-bookings]");
    if (!root) return;

    const statuses = JSON.parse(root.dataset.statuses || "{}");
    const cancelUrlTemplate = root.dataset.cancelUrlTemplate || "#";
    const rescheduleUrl = root.dataset.rescheduleUrl || "#";

    const cards = () => [...root.querySelectorAll("[data-booking-card]")];
    const tabs = [...root.querySelectorAll("[data-bookings-tab]")];
    const statusEl = root.querySelector("[data-bookings-status]");
    const readCard = (card) => JSON.parse(card.dataset.booking);
    const writeCard = (card, data) =>
        (card.dataset.booking = JSON.stringify(data));

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

    function updateCounts() {
        const all = cards();
        root.querySelectorAll("[data-bookings-count]").forEach((el) => {
            const key = el.dataset.bookingsCount;
            el.textContent =
                key === "all"
                    ? all.length
                    : all.filter((card) => card.dataset.group === key).length;
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

        // Why it was cancelled or declined, when there is a reason.
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
    });

    // Cancel modal: the shared action modal, with a required reason.
    const cancelModalEl = document.getElementById("cancelBookingModal");
    let cancelCard = null;

    function showPageStatus(message) {
        statusEl.textContent = message;
        statusEl.classList.remove("d-none");
    }

    // Swap the card to the "cancelled by you" state with what the server answered.
    function markCancelled(card, result, reason) {
        const data = readCard(card);
        const meta = statuses.user_cancelled ?? {
            label: "Cancelled by you",
            icon: "x-circle",
            tone: "danger",
        };
        const next = {
            ...data,
            status: result.status ?? "user_cancelled",
            label: meta.label,
            icon: meta.icon,
            tone: meta.tone,
            group: "cancelled",
            can_cancel: false,
            can_reschedule: Boolean(result.can_reschedule),
            reason: result.status_reason ?? reason,
            reason_label: "Reason for cancellation",
            reason_by: "You",
        };
        writeCard(card, next);
        card.dataset.group = "cancelled";
        card.dataset.status = next.status;
        paintBadge(card.querySelector("[data-booking-badge]"), next);

        const actions = card.querySelector("[data-booking-actions]");
        actions.querySelector("[data-booking-cancel]")?.remove();
        if (next.can_reschedule) {
            const link = document.createElement("a");
            link.className = "account-btn account-btn--primary";
            link.href = `${rescheduleUrl}?reschedule=${encodeURIComponent(next.id)}`;
            link.textContent = "Reschedule";
            link.setAttribute("data-booking-reschedule", "");
            actions.append(link);
        }
    }

    // The card's Cancel booking button opens the modal (it no longer uses data-bs-toggle).
    root.addEventListener("click", async (event) => {
        const button = event.target.closest("[data-booking-cancel]");
        if (!button || !cancelModalEl) return;
        const card = button.closest("[data-booking-card]");
        if (!card) return;
        cancelCard = card;
        const { id, reference, event: eventName } = readCard(card);

        await openActionModal(cancelModalEl, {
            title: "Cancel this booking?",
            message: `You are about to cancel ${eventName} (${reference}). You can reschedule it once, within the same month, after you cancel.`,
            confirmLabel: "Cancel booking",
            tone: "danger",
            reason: true,
            reasonLabel: "Reason for cancellation",
            reasonHint:
                "Tell J&G why you are cancelling. This is saved in your booking details.",
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
                            "We couldn't cancel this booking. Please try again.",
                    );
                }

                markCancelled(card, data.booking ?? {}, reason);
                updateCounts();
                applyFilter();
                showPageStatus(
                    data.message || `Booking ${reference} has been cancelled.`,
                );
            },
        });
        cancelCard = null;
    });

    applyFilter();
}

document.addEventListener("DOMContentLoaded", initBookings);
