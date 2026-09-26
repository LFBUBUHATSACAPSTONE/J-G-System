/**
 * Event Information step (booking flow) — submit (AJAX) + live field
 * feedback. Same pattern as client-information.js: novalidate on the
 * form, every required-field/format check enforced here in JS, fetch()
 * on submit, inline errors under each field.
 *
 * Two things this step has that client-information.js doesn't:
 *   - a custom Event Type select (button + listbox, backed by a hidden
 *     input so it posts like any other form field)
 *   - the Indoor/Outdoor/Both checkboxes, which are mutually exclusive
 *     (checking one unchecks the others) rather than a real radio group,
 *     to match the provided design
 *
 * The Continue button lives outside <form> (in the aside action column)
 * and is wired via form="event-information-form", so a click still fires
 * the form's native "submit" event.
 *
 * The Previous button is back-end/router agnostic: this module only
 * dispatches a bubbling `booking:previous` CustomEvent from it, the same
 * way client-information.js dispatches `booking:cancel`. Wire a listener
 * to it wherever "previous" should actually go once the step router
 * exists.
 *
 * Expected back-end contract for `booking.event-information`:
 *   200 JSON -> saved, caller advances to the next step
 *   422 {"message": "…", "errors": {"event_name": ["…"], …}} -> rejected
 */

import {
    getRequiredError,
    getPhoneError,
    wireLiveField,
    setFieldState,
} from "../auth/validation.js";

const FORM_ID = "event-information-form";

function initEventInformation() {
    const form = document.getElementById(FORM_ID);
    if (!form) return;

    wireLiveField(form, "event_name", (v) => getRequiredError(v, "Event name"));
    wireLiveField(form, "event_location", (v) =>
        getRequiredError(v, "Event location"),
    );
    // Optional field, so no error while empty — but unlike wireLiveField
    // (which waits for a first blur before validating), this checks from
    // the very first keystroke, same as the auth modal's identifier field.
    initVenueContactLive(form);

    initEventTypeSelect(form);
    initGuestCountFilter(form);
    initVenueType(form);

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitEventInformation(form);
    });

    form.addEventListener("input", () => clearError(form));
    form.addEventListener("change", () => clearError(form));

    // Scoped to this step's own wrapper — see the matching note in
    // client-information.js for why (every step's markup now coexists
    // in the DOM, toggled via d-none by booking-flow.js).
    const step = form.closest(".booking-flow__view") || document;
    const previousBtn = step.querySelector("[data-booking-previous]");
    previousBtn?.addEventListener("click", () => {
        previousBtn.dispatchEvent(
            new CustomEvent("booking:previous", { bubbles: true }),
        );
    });
}

/*
 Custom Event Type select: a button (data-select-toggle) opens a listbox
 (data-select-options); picking an <li> sets its text on the button
 (data-select-value) and its value on a hidden input (data-select-input),
 which is what actually gets posted as `event_type`.
*/
function initEventTypeSelect(form) {
    const wrapper = form.querySelector("[data-select]");
    if (!wrapper) return;

    const toggle = wrapper.querySelector("[data-select-toggle]");
    const options = wrapper.querySelector("[data-select-options]");
    const valueEl = wrapper.querySelector("[data-select-value]");
    const hiddenInput = wrapper.querySelector("[data-select-input]");
    const items = [...wrapper.querySelectorAll("[data-value]")];
    const errorEl = wrapper.querySelector('[data-field-error="event_type"]');

    const close = () => {
        options.classList.add("d-none");
        toggle.setAttribute("aria-expanded", "false");
    };

    const open = () => {
        options.classList.remove("d-none");
        toggle.setAttribute("aria-expanded", "true");
    };

    const select = (item) => {
        items.forEach((el) => el.removeAttribute("aria-selected"));
        item.setAttribute("aria-selected", "true");
        valueEl.textContent = item.dataset.value;
        valueEl.removeAttribute("data-placeholder-active");
        hiddenInput.value = item.dataset.value;
        setFieldState(toggle, errorEl, "");
        close();
        toggle.focus();
    };

    toggle.addEventListener("click", () => {
        toggle.getAttribute("aria-expanded") === "true" ? close() : open();
    });

    items.forEach((item) => {
        item.addEventListener("click", () => select(item));
    });

    document.addEventListener("click", (event) => {
        if (!wrapper.contains(event.target)) close();
    });

    toggle.addEventListener("keydown", (event) => {
        if (event.key === "Escape") close();
    });
}

/*
 Venue Contact Person is optional, so it can't use wireLiveField as-is
 (that gates every field's live check behind a first blur). This runs the
 same getPhoneError check on every keystroke from the first character —
 matching the auth modal's Email or Phone field — while still showing no
 error for an empty, untouched field.
*/
function initVenueContactLive(form) {
    const input = form.querySelector('[name="venue_contact_person"]');
    const errorEl = form.querySelector(
        '[data-field-error="venue_contact_person"]',
    );
    if (!input) return;

    input.addEventListener("input", () => {
        const message = input.value.trim() ? getPhoneError(input.value) : "";
        setFieldState(input, errorEl, message);
    });
}

/*
 Number of Guests must contain digits only — this field switched from
 type="number" to type="text" so we can strip anything non-numeric as the
 user types instead of relying on browser number-input quirks (which
 still let "e", "+", "-", "." through in some browsers).
*/
function initGuestCountFilter(form) {
    const input = form.querySelector('[name="guest_count"]');
    input?.addEventListener("input", () => {
        input.value = input.value.replace(/\D/g, "");
    });
}

/*
 Indoor/Outdoor/Both are plain checkboxes in the markup (not a native
 radio group) but behave like one: checking any of them unchecks the
 other two. At least one is required — enforced in submitEventInformation.
*/
function initVenueType(form) {
    const boxes = [...form.querySelectorAll("[data-venue-type-option]")];
    boxes.forEach((box) => {
        box.addEventListener("change", () => {
            if (box.checked) {
                boxes.forEach((other) => {
                    if (other !== box) other.checked = false;
                });
            }
        });
    });
}

async function submitEventInformation(form) {
    const field = (name) => form.querySelector(`[name="${name}"]`);
    const eventNameInput = field("event_name");
    const eventTypeInput = field("event_type");
    const eventLocationInput = field("event_location");
    const venueContactInput = field("venue_contact_person");
    const guestCountInput = field("guest_count");
    const venueTypeInput = form.querySelector(
        "[data-venue-type-option]:checked",
    );
    const eventTypeToggle = form.querySelector("[data-select-toggle]");
    const submitBtn = document.querySelector(
        `[form="${form.id}"][type="submit"]`,
    );

    clearError(form);

    const checks = [
        [eventNameInput, getRequiredError(eventNameInput.value, "Event name")],
        [
            eventTypeToggle,
            eventTypeInput.value ? "" : "Select an event type.",
            "event_type",
        ],
        [
            eventLocationInput,
            getRequiredError(eventLocationInput.value, "Event location"),
        ],
        // Venue Contact Person is optional — only validated as a phone
        // number if the user actually typed something in it.
        [
            venueContactInput,
            venueContactInput.value.trim()
                ? getPhoneError(venueContactInput.value)
                : "",
        ],
        [
            form.querySelector("[data-venue-type]"),
            venueTypeInput ? "" : "Select a venue type.",
            "venue_type",
        ],
    ];
    const firstFailure = checks.find(([, message]) => message);
    if (firstFailure) {
        const [input, message, fieldName] = firstFailure;
        const errorEl = form.querySelector(
            `[data-field-error="${fieldName ?? input.name}"]`,
        );
        setFieldState(input, errorEl, message);
        input.focus();
        return;
    }

    if (submitBtn) submitBtn.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    form.querySelector('input[name="_token"]')?.value ?? "",
            },
            body: JSON.stringify({
                event_name: eventNameInput.value.trim(),
                event_type: eventTypeInput.value,
                event_location: eventLocationInput.value.trim(),
                venue_contact_person: venueContactInput.value.trim() || null,
                guest_count: guestCountInput.value
                    ? Number(guestCountInput.value)
                    : null,
                venue_type: venueTypeInput?.value ?? null,
            }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            const errors = data.errors ?? {};
            const invalidInputs = Object.keys(errors)
                .map((name) => {
                    if (name === "event_type") return eventTypeToggle;
                    if (name === "venue_type")
                        return form.querySelector("[data-venue-type]");
                    return form.querySelector(`[name="${name}"]`);
                })
                .filter(Boolean);
            showError(
                form,
                Object.values(errors)[0]?.[0] ||
                    data.message ||
                    "We couldn't save the event details. Please review the fields above and try again.",
                invalidInputs,
            );
            return;
        }

        form.dispatchEvent(
            new CustomEvent("booking:event-information-saved", {
                bubbles: true,
            }),
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

function showError(form, message, invalidInputs = []) {
    const errorEl = form.querySelector("[data-event-info-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
    invalidInputs.forEach((input) => input.classList.add("is-invalid"));
}

function clearError(form) {
    const errorEl = form.querySelector("[data-event-info-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    form.querySelectorAll(
        "input, button[data-select-toggle], fieldset[data-venue-type]",
    ).forEach((input) => input.classList.remove("is-invalid"));
}

document.addEventListener("DOMContentLoaded", initEventInformation);

export { initEventInformation };
