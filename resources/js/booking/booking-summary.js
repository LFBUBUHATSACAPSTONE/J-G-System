/**
 * Booking Summary + Payment step (booking flow). This step has no
 * editable recap fields — those <input readonly> elements are display
 * only — so there's no live per-field validation like the earlier
 * steps. What this file owns:
 *
 *   - a two-option toggle (data-payment-option-group / data-payment-option)
 *     for Full Payment vs Down Payment, same role="radio" +
 *     aria-checked pattern as event-information's venue-type checkboxes,
 *     writing the chosen value into the hidden
 *     input[data-payment-option-input] that gets submitted with the form.
 *   - the same fetch() AJAX submit shape as event-schedule.js /
 *     client-information.js: clear errors -> require a payment option ->
 *     disable submit -> fetch() with Accept / Content-Type /
 *     X-CSRF-TOKEN -> on !response.ok read data.errors -> on success
 *     dispatch a bubbling CustomEvent.
 *   - Previous and Cancel both just dispatch bubbling CustomEvents
 *     (`booking:previous`, `booking:cancel`), the same technique
 *     event-schedule.js / client-information.js use — neither button
 *     lives inside <form> here, so nothing calls submit() directly.
 *
 * Expected back-end contract for `booking.payment`:
 *   200 JSON -> saved, caller advances to the Confirmation step
 *   422 {"message": "…", "errors": {"payment_option": ["…"]}} -> rejected
 *   422 {"message": "…", "full_dates": ["YYYY-MM-DD", …]}
 *       -> a chosen day reached the event limit meanwhile;
 *
 * Reschedule mode (form[data-reschedule-id]): the
 * payment is carried over, so there is no payment option to pick. The body is
 * { reschedule_id } instead of { payment_option }; 403 {message} if the booking can't be
 * rescheduled. The "Confirm Reschedule" button sits in the actions row (form="payment-form").
 */

import { getCsrfToken } from "../auth/csrf.js";

const FORM_ID = "payment-form";

function initBookingSummary() {
    initPaymentOptionToggle();

    const form = document.getElementById(FORM_ID);
    if (!form) return;

    // Scoped to this step's own wrapper — every step's markup now
    // coexists in the DOM (toggled via d-none by booking-flow.js), so an
    // unscoped query would grab another step's Previous/Cancel button
    // instead of this one's.
    const step = form.closest(".booking-flow__view") || document;
    wireActionButtons(step);

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitPayment(form);
    });
}

function initPaymentOptionToggle() {
    const group = document.querySelector("[data-payment-option-group]");
    const hiddenInput = document.querySelector("[data-payment-option-input]");
    if (!group || !hiddenInput) return;

    const buttons = group.querySelectorAll("[data-payment-option]");

    buttons.forEach((btn) => {
        btn.addEventListener("click", () => {
            buttons.forEach((other) =>
                other.setAttribute("aria-checked", "false"),
            );
            btn.setAttribute("aria-checked", "true");
            hiddenInput.value = btn.dataset.paymentOption;

            const form = btn.closest("form");
            const errorEl = form?.querySelector(
                '[data-field-error="payment_option"]',
            );
            errorEl?.classList.add("d-none");
        });
    });
}

function wireActionButtons(scope = document) {
    scope.querySelectorAll("[data-booking-previous]").forEach((btn) => {
        btn.addEventListener("click", () => {
            btn.dispatchEvent(
                new CustomEvent("booking:previous", { bubbles: true }),
            );
        });
    });

    scope.querySelectorAll("[data-booking-cancel]").forEach((btn) => {
        btn.addEventListener("click", () => {
            btn.dispatchEvent(
                new CustomEvent("booking:cancel", { bubbles: true }),
            );
        });
    });
}

async function submitPayment(form) {
    clearError(form);

    const hiddenInput = form.querySelector("[data-payment-option-input]");
    const rescheduleId = form.dataset.rescheduleId;
    if (!rescheduleId && !hiddenInput?.value) {
        showError(form, "Please select a payment option.");
        return;
    }

    const submitBtn = document.querySelector(
        '[form="payment-form"][type="submit"]',
    );
    if (submitBtn) submitBtn.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(form),
            },
            body: JSON.stringify(
                rescheduleId
                    ? { reschedule_id: rescheduleId }
                    : { payment_option: hiddenInput.value },
            ),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            // Capacity race: a chosen day filled up after Event Schedule saved. Hand it to the flow.
            if (Array.isArray(data.full_dates) && data.full_dates.length) {
                form.dispatchEvent(
                    new CustomEvent("booking:schedule-conflict", {
                        bubbles: true,
                        detail: {
                            fullDates: data.full_dates,
                            message: data.message,
                        },
                    }),
                );
                return;
            }

            const message =
                data.errors?.payment_option?.[0] ??
                data.message ??
                "Something went wrong. Please try again.";
            showError(form, message);
            return;
        }

        form.dispatchEvent(
            new CustomEvent("booking:payment-saved", { bubbles: true }),
        );
    } catch {
        showError(
            form,
            "Something went wrong on our end. Please check your connection and try again.",
        );
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
}

function showError(form, message) {
    const errorEl = form.querySelector("[data-payment-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
}

function clearError(form) {
    const errorEl = form.querySelector("[data-payment-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
}

document.addEventListener("DOMContentLoaded", initBookingSummary);

export { initBookingSummary };
