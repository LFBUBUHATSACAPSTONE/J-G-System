/**
 * Account > My Bookings: tab filter, details modal and cancel request.
 * FRONT END ONLY. Every booking comes from the server-rendered cards (their data-booking JSON); this
 * file never decides a status, it shows what the server answers. Same pattern as profile.js.
 *
 * Back-end contract for `user.account.bookings.cancel` (POST, empty JSON body, {booking} = id):
 *   200 {"ok": true, "message": "…", "booking": {"id": "…", "status": "user_cancelled", "can_reschedule": true|false}}
 *   401 / 403 / 409 / 422 {"message": "…"}  -> shown inside the confirmation modal
 * Reschedule links point at the existing `user.booking?reschedule=<id>` page (data-reschedule-url).
 */

import { Modal } from "bootstrap";
import { getCsrfToken } from "../auth/csrf.js";

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
    });

    // Cancel modal
    const cancelModalEl = document.getElementById("cancelBookingModal");
    const confirmBtn = cancelModalEl?.querySelector("[data-cancel-confirm]");
    const errorEl = cancelModalEl?.querySelector("[data-cancel-error]");
    let cancelCard = null;

    const showCancelError = (message) => {
        errorEl.textContent = message;
        errorEl.classList.toggle("d-none", !message);
    };

    cancelModalEl?.addEventListener("show.bs.modal", (event) => {
        cancelCard =
            event.relatedTarget?.closest("[data-booking-card]") ?? null;
        if (!cancelCard) return;
        const data = readCard(cancelCard);
        cancelModalEl.querySelector('[data-cancel="event"]').textContent =
            data.event;
        cancelModalEl.querySelector('[data-cancel="reference"]').textContent =
            data.reference;
        showCancelError("");
        confirmBtn.disabled = false;
    });

    function showPageStatus(message) {
        statusEl.textContent = message;
        statusEl.classList.remove("d-none");
    }

    // Swap the card to the "cancelled by you" state with what the server answered.
    function markCancelled(card, result) {
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

    confirmBtn?.addEventListener("click", async () => {
        if (!cancelCard) return;
        const card = cancelCard;
        const { id, reference } = readCard(card);
        showCancelError("");
        confirmBtn.disabled = true;

        try {
            const response = await fetch(
                cancelUrlTemplate.replace("__ID__", encodeURIComponent(id)),
                {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                    body: "{}",
                },
            );
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                showCancelError(
                    data.message ||
                        "We couldn't cancel this booking. Please try again.",
                );
                confirmBtn.disabled = false;
                return;
            }

            markCancelled(card, data.booking ?? {});
            updateCounts();
            applyFilter();
            Modal.getInstance(cancelModalEl)?.hide();
            showPageStatus(
                data.message || `Booking ${reference} has been cancelled.`,
            );
        } catch {
            showCancelError(
                "Something went wrong on our end. Please check your connection and try again.",
            );
            confirmBtn.disabled = false;
        }
    });

    applyFilter();
}

document.addEventListener("DOMContentLoaded", initBookings);
