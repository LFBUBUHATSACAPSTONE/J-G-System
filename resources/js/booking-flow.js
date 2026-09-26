/**
 * Booking flow controller — the booking-step equivalent of auth-modal.js's
 * setAuthView() pattern. Every step's markup lives in the DOM at once
 * (each wrapped in .booking-flow__view[data-view="..."] inside
 * #bookingFlow, most of them starting with a d-none), and this file is
 * the only thing that knows how they're ordered and switches which one
 * is visible.
 *
 * Every step module (client-information.js, event-information.js,
 * event-schedule.js, booking-summary.js, booking-confirmation.js) only
 * dispatches bubbling CustomEvents — it doesn't know or care where
 * "continue"/"previous"/"cancel" actually goes. This file is that
 * listener, same relationship auth-modal.js has to login.js / signup.js
 * / forgot-password.js / etc.
 *
 * Package step: NOT YET BUILT. It isn't in STEP_ORDER and there's no
 * .booking-flow__view[data-view="package"] in booking.blade.php yet, so
 * "client-information" is the flow's current entry point (index 0).
 * Once Package exists:
 *   1. Add "package" to the front of STEP_ORDER below.
 *   2. Add its view wrapper to booking.blade.php ahead of
 *      client-information's, as data-view="package" (no d-none, since
 *      it becomes the new first/visible step).
 *   3. Give client-information's wrapper a d-none like the others.
 *   4. Package's own JS should dispatch a bubbling
 *      `booking:package-saved` CustomEvent when a package is chosen —
 *      same technique every other step uses — and should save the
 *      chosen package (name/cost) somewhere booking-summary.blade.php's
 *      $packageName / $packageCost can be read from once a controller
 *      exists (session, most likely, matching how every other step's
 *      data will eventually get there).
 *   5. Add `root.addEventListener("booking:package-saved", () =>
 *      advanceTo(root, "client-information"));` below, next to the
 *      other advance-on-save listeners.
 * That's the only file this needs touching in.
 */

const FLOW_ID = "bookingFlow";
const VIEW_SELECTOR = ".booking-flow__view";

// Package is not yet built — see the file-level note above. Once it is, prepend "package" here.
const STEP_ORDER = [
    "client-information",
    "event-information",
    "event-schedule",
    "booking-summary",
    "booking-confirmation",
];

function initBookingFlow() {
    const root = document.getElementById(FLOW_ID);
    if (!root) return;

    setBookingView(
        root,
        root.getAttribute("data-current-view") || STEP_ORDER[0],
    );

    // Each of these fires once its step's own AJAX submit succeeds.
    root.addEventListener("booking:personal-information-saved", () =>
        advanceTo(root, "event-information"),
    );
    root.addEventListener("booking:event-information-saved", () =>
        advanceTo(root, "event-schedule"),
    );
    root.addEventListener("booking:event-schedule-saved", () =>
        advanceTo(root, "booking-summary"),
    );
    root.addEventListener("booking:payment-saved", () =>
        advanceTo(root, "booking-confirmation"),
    );

    // "Previous" is dispatched by whichever step is currently visible (event-information, event-schedule, booking-summary all use the same event name) — the controller just steps back one in STEP_ORDER from whatever's current, so it doesn't need to know which one fired it.
    root.addEventListener("booking:previous", () => goToPrevious(root));

    // "Cancel" (client-information, booking-summary) and "continue" (booking-confirmation's single final button) both leave the flow entirely, so they're handled the same way: send the person to whatever URL booking.blade.php put on the root element.
    root.addEventListener("booking:cancel", () =>
        leaveFlow(root, "cancel-url"),
    );
    root.addEventListener("booking:continue", () =>
        leaveFlow(root, "continue-url"),
    );
}

function advanceTo(root, view) {
    setBookingView(root, view);
}

function goToPrevious(root) {
    const current = root.getAttribute("data-current-view");
    const index = STEP_ORDER.indexOf(current);

    if (index > 0) {
        setBookingView(root, STEP_ORDER[index - 1]);
        return;
    }

    // Already at the first built step. Once Package exists this is
    // where client-information's Previous would go instead — for now
    // there's nowhere earlier to go, so treat it the same as Cancel.
    leaveFlow(root, "cancel-url");
}

function leaveFlow(root, urlAttr) {
    const url = root.dataset[toCamelCase(urlAttr)];
    if (url) window.location.href = url;
}

function toCamelCase(kebab) {
    return kebab.replace(/-([a-z])/g, (_, c) => c.toUpperCase());
}

function setBookingView(root, view) {
    const views = root.querySelectorAll(VIEW_SELECTOR);
    let matched = false;

    views.forEach((el) => {
        const isTarget = el.dataset.view === view;
        el.classList.toggle("d-none", !isTarget);
        if (isTarget) matched = true;
    });

    root.setAttribute("data-current-view", matched ? view : STEP_ORDER[0]);
    if (!matched) {
        setBookingView(root, STEP_ORDER[0]);
    }
}

document.addEventListener("DOMContentLoaded", initBookingFlow);

export { initBookingFlow, setBookingView, STEP_ORDER };
