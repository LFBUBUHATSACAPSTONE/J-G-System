/**
 * Progress tracker — read-only observer of #bookingFlow's
 * data-current-view attribute, the same one booking-flow.js already maintains. Doesn't dispatch or listen for any booking:* events itself; it only reacts to the attribute (via MutationObserver) and, on click, calls booking-flow.js's exported setBookingView() directly to jump to an already-unlocked step.
 */

import { setBookingView, STEP_ORDER } from "../booking-flow.js";

const FLOW_ID = "bookingFlow";
const TRACKER_SELECTOR = ".progress-tracker";

function initProgressTracker() {
    const tracker = document.querySelector(TRACKER_SELECTOR);
    const root = document.getElementById(FLOW_ID);
    if (!tracker || !root) return;

    const steps = Array.from(
        tracker.querySelectorAll(".progress-tracker__step"),
    );
    const fill = tracker.querySelector("[data-progress-fill]");

    let furthestIndex = 0;

    function render() {
        const currentView = root.getAttribute("data-current-view");
        const currentIndex = STEP_ORDER.indexOf(currentView);
        if (currentIndex > furthestIndex) furthestIndex = currentIndex;

        steps.forEach((el) => {
            const index = STEP_ORDER.indexOf(el.dataset.step);
            const circle = el.querySelector(".progress-tracker__circle");

            el.classList.remove("is-locked", "is-open", "is-numbered");

            if (index > furthestIndex) {
                el.classList.add("is-locked");
                circle.setAttribute("disabled", "");
                return;
            }

            circle.removeAttribute("disabled");
            el.classList.add(index <= currentIndex ? "is-numbered" : "is-open");
        });

        if (fill)
            fill.style.width = `${(currentIndex / (STEP_ORDER.length - 1)) * 100}%`;
    }

    steps.forEach((el) => {
        const circle = el.querySelector(".progress-tracker__circle");
        circle.addEventListener("click", () => {
            if (circle.hasAttribute("disabled")) return;
            setBookingView(root, el.dataset.step);
        });
    });

    new MutationObserver((mutations) => {
        if (mutations.some((m) => m.attributeName === "data-current-view"))
            render();
    }).observe(root, { attributes: true });

    render();
}

document.addEventListener("DOMContentLoaded", initProgressTracker);

export { initProgressTracker };
