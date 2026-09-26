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
 */

const FORM_ID = "payment-form";

function initBookingSummary() {
    initPaymentOptionToggle();
    wireActionButtons();

    const form = document.getElementById(FORM_ID);
    if (!form) return;

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

function wireActionButtons() {
    document.querySelectorAll("[data-booking-previous]").forEach((btn) => {
        btn.addEventListener("click", () => {
            btn.dispatchEvent(
                new CustomEvent("booking:previous", { bubbles: true }),
            );
        });
    });

    document.querySelectorAll("[data-booking-cancel]").forEach((btn) => {
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
    if (!hiddenInput?.value) {
        showError(form, "Please select a payment option.");
        return;
    }

    const submitBtn = form.querySelector('[form="payment-form"]');
    if (submitBtn) submitBtn.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": form.querySelector('input[name="_token"]')
                    .value,
            },
            body: JSON.stringify({ payment_option: hiddenInput.value }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
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
