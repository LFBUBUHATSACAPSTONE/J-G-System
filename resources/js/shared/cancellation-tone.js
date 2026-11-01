/**
 * Paints the state of a cancellation card (components/booking/cancellation-card.blade.php).
 *
 * The card's colours come from `data-tone` on the section (pending | cancelled | declined | neutral); the
 * icon and the pill are the two elements marked `data-cc-icon` and `data-cc-pill`. Shared by the admin
 * modal (js/admin/cancellation.js) and My Bookings (js/account/bookings.js). The title, meta line and
 * reasons stay with each caller because their wording differs (admin: "Client's reason", customer: "Your reason").
 *
 * FRONT END ONLY: `status` is whatever the server sent in `cancellation.status`.
 */

const STATES = {
    pending: { tone: "pending", pill: "Pending", icon: "clock" },
    approved: { tone: "cancelled", pill: "Approved", icon: "x-circle" },
    declined: { tone: "declined", pill: "Declined", icon: "calendar-check" },
};

// cancelled_by_admin, cancelled, user_cancelled and anything unknown
const FALLBACK = { tone: "cancelled", pill: "Cancelled", icon: "x-circle" };

export function paintCancellationTone(section, status) {
    if (!section) return;

    const state = STATES[status] ?? FALLBACK;
    section.dataset.tone = state.tone;

    const pill = section.querySelector("[data-cc-pill]");
    if (pill) pill.textContent = state.pill;

    const icon = section.querySelector("[data-cc-icon]");
    if (icon) icon.className = `ph ph-${state.icon}`;
}
