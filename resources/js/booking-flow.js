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
 * Locking: once the flow reaches Booking Confirmation (the payment was
 * submitted and now awaits verification) the root gets `data-flow-locked`.
 * From then on setBookingView() refuses any other view, so Previous, the
 * progress tracker and the schedule-conflict handler cannot reopen an
 * earlier step. Only Continue (leaves the flow) remains.
 *
 * Package is step 1 in STEP_ORDER (so the progress tracker can show it
 * as complete) but has no view here: it's chosen on the landing page,
 * whose "Book Now" links to /user/booking. ENTRY_VIEW is where the flow
 * actually starts.
 */

const FLOW_ID = "bookingFlow";
const VIEW_SELECTOR = ".booking-flow__view";

const ENTRY_VIEW = "client-information";
const FINAL_VIEW = "booking-confirmation";
const LOCK_ATTR = "data-flow-locked";

const STEP_ORDER = [
    "package",
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
        root.getAttribute("data-current-view") || entryViewOf(root),
    );

    // Each of these fires once its step's own AJAX submit succeeds.
    root.addEventListener("booking:personal-information-saved", () => {
        updatePersonalSummary(root);
        advanceTo(root, "event-information");
    });
    root.addEventListener("booking:event-information-saved", () => {
        updateEventSummary(root);
        advanceTo(root, "event-schedule");
    });
    root.addEventListener("booking:event-schedule-saved", () => {
        updateScheduleSummary(root);
        advanceTo(root, "booking-summary");
    });
    root.addEventListener("booking:payment-saved", (event) => {
        const reference = event.detail?.reference;
        const referenceEl = root.querySelector("[data-booking-reference]");
        if (reference && referenceEl) referenceEl.textContent = `#${reference}`;
        advanceTo(root, "booking-confirmation");
    });

    // A later step was rejected because a chosen day filled up (capacity race). Send the client
    // back to Event Schedule; event-schedule.js listens for the same event to mark the day full.
    root.addEventListener("booking:schedule-conflict", () =>
        advanceTo(root, "event-schedule"),
    );

    // "Previous" is dispatched by whichever step is currently visible
    // (event-information, event-schedule, booking-summary all use the
    // same event name) — the controller just steps back one in
    // STEP_ORDER from whatever's current, so it doesn't need to know
    // which one fired it.
    root.addEventListener("booking:previous", () => goToPrevious(root));

    // "Cancel" (client-information, booking-summary) and "continue"
    // (booking-confirmation's single final button) both leave the flow
    // entirely, so they're handled the same way: send the person to
    // whatever URL booking.blade.php put on the root element.
    root.addEventListener("booking:cancel", () =>
        leaveFlow(root, "cancel-url"),
    );
    root.addEventListener("booking:continue", () =>
        leaveFlow(root, "continue-url"),
    );
}

function updatePersonalSummary(root) {
    const form = root.querySelector("#personal-information-form");
    if (!form) return;
    const value = (name) => form.elements.namedItem(name)?.value?.trim() ?? "";

    setSummaryValue(root, "fullName", `${value("first_name")} ${value("last_name")}`.trim());
    setSummaryValue(root, "email", value("email"));
    setSummaryValue(root, "contactNumber", value("contact_number"));
}

function updateEventSummary(root) {
    const form = root.querySelector("#event-information-form");
    if (!form) return;
    const value = (name) => form.elements.namedItem(name)?.value?.trim() ?? "";
    const eventType = value("event_type") === "Others"
        ? value("event_type_other")
        : value("event_type");

    setSummaryValue(root, "eventName", value("event_name"));
    setSummaryValue(root, "eventType", eventType);
    setSummaryValue(root, "eventLocation", value("event_location"));
    setSummaryValue(root, "eventContactPerson", value("venue_contact_person"));
}

function updateScheduleSummary(root) {
    const form = root.querySelector("#event-schedule-form");
    if (!form) return;
    const start = form.elements.namedItem("event_start_date")?.value;
    const end = form.elements.namedItem("event_end_date")?.value;
    const formatDate = (value) => {
        if (!value) return "";
        const [year, month, day] = value.split("-").map(Number);
        return new Date(year, month - 1, day).toLocaleDateString("en-US", {
            month: "long",
            day: "numeric",
            year: "numeric",
        });
    };
    const startDate = formatDate(start);
    const endDate = formatDate(end);

    setSummaryValue(root, "eventDate", startDate && endDate && start !== end
        ? `${startDate} – ${endDate}`
        : startDate);
    setSummaryValue(root, "startTime", scheduleTime(form, "start_time"));
    setSummaryValue(root, "endTime", scheduleTime(form, "end_time"));
}

function scheduleTime(form, name) {
    const input = form.elements.namedItem(name);
    const period = input?.closest(".event-schedule__field")
        ?.querySelector('[aria-pressed="true"]')?.dataset.ampm;
    return input?.value && period ? `${input.value.trim()} ${period}` : "";
}

function setSummaryValue(root, key, value) {
    const target = root.querySelector(`[data-booking-summary="${key}"]`);
    if (target) target.textContent = value;
}

// Reschedule mode starts at Event Schedule: client and event details come from the original booking.
function entryViewOf(root) {
    return root.dataset.rescheduleId ? "event-schedule" : ENTRY_VIEW;
}

function advanceTo(root, view) {
    setBookingView(root, view);
}

function goToPrevious(root) {
    // Payment already submitted: no step behind Confirmation is reachable.
    if (root.hasAttribute(LOCK_ATTR)) return;

    const current = root.getAttribute("data-current-view");
    const index = STEP_ORDER.indexOf(current);

    if (index > STEP_ORDER.indexOf(entryViewOf(root))) {
        setBookingView(root, STEP_ORDER[index - 1]);
        return;
    }

    // Already at the first step — nowhere earlier to go, so treat it
    // the same as Cancel.
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
    if (root.hasAttribute(LOCK_ATTR) && view !== FINAL_VIEW) return;
    if (view === FINAL_VIEW) root.setAttribute(LOCK_ATTR, "");

    const views = root.querySelectorAll(VIEW_SELECTOR);
    let matched = false;

    views.forEach((el) => {
        const isTarget = el.dataset.view === view;
        el.classList.toggle("d-none", !isTarget);
        if (isTarget) matched = true;
    });

    root.setAttribute("data-current-view", matched ? view : entryViewOf(root));
    if (!matched) {
        setBookingView(root, entryViewOf(root));
    }
}

document.addEventListener("DOMContentLoaded", initBookingFlow);

export { initBookingFlow, setBookingView, STEP_ORDER };
