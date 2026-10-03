/**
 * Personal Information step (booking flow) — submit (AJAX) + live field
 * feedback. Same pattern as the auth modal's other form modules
 * (signup.js, forgot-password.js): novalidate on the form, every
 * required-field/format check enforced here in JS, fetch() on submit,
 * inline errors under each field.
 *
 * The Continue button lives outside <form> (it's in the aside action
 * column) and is wired to the form via form="personal-information-form",
 * so a click still fires the form's native "submit" event — no separate
 * click handler needed for it.
 *
 * The Cancel button is back-end/router agnostic: this module only
 * dispatches a bubbling `booking:cancel` CustomEvent from it, the same
 * way resend-code.js listens for `auth:resend-code`. Wire a listener to
 * it wherever "cancel" should actually go (previous step, landing page,
 * etc.) once that's decided.
 *
 * Expected back-end contract for `booking.personal-information.store`:
 *   200 JSON -> saved, caller advances to the next step
 *   422 {"message": "…", "errors": {"first_name": ["…"], …}} -> rejected
 */

import { getCsrfToken } from "../auth/csrf.js";
import {
    getRequiredError,
    getEmailError,
    getContactNumberError,
    getNameError,
    wireLiveField,
    wireLiveFieldImmediate,
    setFieldState,
} from "../auth/validation.js";

const FORM_ID = "personal-information-form";

function initPersonalInformation() {
    const form = document.getElementById(FORM_ID);
    if (!form) return;

    // First/Last Name: letters only (no digits/symbols), blur-gated like
    // the rest of the auth modal's plain text fields.
    wireLiveField(form, "first_name", (v) => getNameError(v, "First name"));
    wireLiveField(form, "last_name", (v) => getNameError(v, "Last name"));
    // Email and Contact Number give feedback from the very first keystroke
    // rather than waiting for a first blur, since a malformed email/phone
    // is cheap to flag early and the user is about to repeat the same
    // mistake across every character they type. Contact Number must be a
    // PH mobile number starting with 09 — the user types it in fully
    // themselves; nothing auto-inserts or locks the prefix for them.
    wireLiveFieldImmediate(form, "email", getEmailError);
    wireLiveFieldImmediate(form, "contact_number", getContactNumberError);
    wireLiveField(form, "address", (v) => getRequiredError(v, "Address"));

    initNameFilter(form, "first_name");
    initNameFilter(form, "last_name");
    initDigitsOnlyFilter(form, "contact_number");

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitPersonalInformation(form);
    });

    form.addEventListener("input", () => clearError(form));

    // Scoped to this step's own wrapper (.booking-flow__view) so that,
    // now every step's markup coexists in the DOM at once (toggled via
    // d-none by booking-flow.js), this doesn't accidentally grab another
    // step's Cancel button. Falls back to a document-wide query so this
    // still works if the component is ever rendered standalone.
    const step = form.closest(".booking-flow__view") || document;
    const cancelBtn = step.querySelector("[data-booking-cancel]");
    cancelBtn?.addEventListener("click", () => {
        cancelBtn.dispatchEvent(
            new CustomEvent("booking:cancel", { bubbles: true }),
        );
    });
}

/*
 Blocks digits/symbols from ever being typed into First/Last Name — same
 approach as guest_count in event-information.js (filter on "input"
 rather than only flagging it after the fact). getNameError still runs
 via wireLiveField as a backstop (e.g. for pasted text).
*/
function initNameFilter(form, fieldName) {
    const input = form.querySelector(`[name="${fieldName}"]`);
    input?.addEventListener("input", () => {
        input.value = input.value.replace(/[^A-Za-z\s]/g, "");
    });
}

/*
 Blocks anything but digits from ever being typed into a Contact
 Number-style field — same "filter on input" approach as
 initNameFilter/guest_count. getContactNumberError still runs via
 wireLiveFieldImmediate as a backstop (e.g. for pasted text with
 stray characters).
*/
function initDigitsOnlyFilter(form, fieldName) {
    const input = form.querySelector(`[name="${fieldName}"]`);
    input?.addEventListener("input", () => {
        input.value = input.value.replace(/\D/g, "");
    });
}

async function submitPersonalInformation(form) {
    const field = (name) => form.querySelector(`[name="${name}"]`);
    const firstNameInput = field("first_name");
    const lastNameInput = field("last_name");
    const emailInput = field("email");
    const contactInput = field("contact_number");
    const addressInput = field("address");
    const submitBtn = document.querySelector(
        `[form="${form.id}"][type="submit"]`,
    );

    clearError(form);

    const checks = [
        [firstNameInput, getNameError(firstNameInput.value, "First name")],
        [lastNameInput, getNameError(lastNameInput.value, "Last name")],
        [emailInput, getEmailError(emailInput.value)],
        [contactInput, getContactNumberError(contactInput.value)],
        [addressInput, getRequiredError(addressInput.value, "Address")],
    ];
    const firstFailure = checks.find(([, message]) => message);
    if (firstFailure) {
        const [input, message] = firstFailure;
        const errorEl = form.querySelector(
            `[data-field-error="${input.name}"]`,
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
                "X-CSRF-TOKEN": getCsrfToken(form),
            },
            body: JSON.stringify({
                first_name: firstNameInput.value.trim(),
                last_name: lastNameInput.value.trim(),
                email: emailInput.value.trim(),
                contact_number: contactInput.value.trim(),
                address: addressInput.value.trim(),
            }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            const errors = data.errors ?? {};
            const invalidInputs = Object.keys(errors)
                .map((name) => form.querySelector(`[name="${name}"]`))
                .filter(Boolean);
            showError(
                form,
                Object.values(errors)[0]?.[0] ||
                    data.message ||
                    "We couldn't save your information. Please review the fields above and try again.",
                invalidInputs,
            );
            return;
        }

        form.dispatchEvent(
            new CustomEvent("booking:personal-information-saved", {
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
    const errorEl = form.querySelector("[data-personal-info-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
    invalidInputs.forEach((input) => input.classList.add("is-invalid"));
}

function clearError(form) {
    const errorEl = form.querySelector("[data-personal-info-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    form.querySelectorAll("input").forEach((input) =>
        input.classList.remove("is-invalid"),
    );
}

document.addEventListener("DOMContentLoaded", initPersonalInformation);

export { initPersonalInformation };