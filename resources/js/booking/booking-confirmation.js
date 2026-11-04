/**
 * Booking Confirmation step (booking flow, final). No form, no fields,
 * no AJAX submit — this screen is a read-only end state. The single
 * Continue button just dispatches a bubbling CustomEvent
 * (`booking:continue`), the same technique event-schedule.js /
 * client-information.js / booking-summary.js use for Previous/Cancel,
 * so whatever's listening at the flow level (e.g. redirect to the
 * dashboard/landing page) can react without this file needing to know
 * where "continue" goes.
 */

function initBookingConfirmation() {
    document.querySelectorAll("[data-booking-continue]").forEach((btn) => {
        btn.addEventListener("click", () => {
            btn.dispatchEvent(
                new CustomEvent("booking:continue", { bubbles: true }),
            );
        });
    });
}

document.addEventListener("DOMContentLoaded", initBookingConfirmation);

export { initBookingConfirmation };
