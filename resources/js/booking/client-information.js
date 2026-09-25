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

import {
    getRequiredError,
    getEmailError,
    getPhoneError,
    wireLiveField,
    setFieldState,
} from "../auth/validation.js";

const FORM_ID = "personal-information-form";

function initPersonalInformation() {
    const form = document.getElementById(FORM_ID);
    if (!form) return;

    wireLiveField(form, "first_name", (v) => getRequiredError(v, "First name"));
    wireLiveField(form, "last_name", (v) => getRequiredError(v, "Last name"));
    wireLiveField(form, "email", getEmailError);
    wireLiveField(form, "contact_number", getPhoneError);
    wireLiveField(form, "address", (v) => getRequiredError(v, "Address"));

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitPersonalInformation(form);
    });

    form.addEventListener("input", () => clearError(form));

    const cancelBtn = document.querySelector("[data-booking-cancel]");
    cancelBtn?.addEventListener("click", () => {
        cancelBtn.dispatchEvent(
            new CustomEvent("booking:cancel", { bubbles: true }),
        );
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
        [firstNameInput, getRequiredError(firstNameInput.value, "First name")],
        [lastNameInput, getRequiredError(lastNameInput.value, "Last name")],
        [emailInput, getEmailError(emailInput.value)],
        [contactInput, getPhoneError(contactInput.value)],
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
                "X-CSRF-TOKEN":
                    form.querySelector('input[name="_token"]')?.value ?? "",
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
