/**
 * Account > Profile: live field feedback + AJAX save. Same pattern as the booking Client
 * Information step (booking/client-information.js): novalidate on the form, every check enforced
 * here, fetch() on submit, an inline message under each field.
 *
 * Save stays disabled until something changes; "Discard changes" restores the saved values.
 *
 * Back-end contract for `user.account.profile.update` (PATCH, JSON body
 * {first_name, last_name, phone, address}; email is read-only and not sent):
 *   200 {"ok": true, "message": "…", "profile": {…}}  -> saved
 *   422 {"message": "…", "errors": {"phone": ["…"], …}} -> rejected
 *   401 {"message": "…"}                              -> not logged in
 */

import { getCsrfToken } from "../auth/csrf.js";
import {
    getRequiredError,
    getNameError,
    getContactNumberError,
    wireLiveField,
    setFieldState,
} from "../auth/validation.js";

const FORM_ID = "account-profile-form";
const FIELDS = ["first_name", "last_name", "phone", "address"];

// Phone and address are optional, but when a phone is given it must be a valid PH mobile number.
const phoneError = (value) =>
    value.trim() ? getContactNumberError(value) : "";

function initProfile() {
    const form = document.getElementById(FORM_ID);
    if (!form) return;

    const field = (name) => form.querySelector(`[name="${name}"]`);
    const saveBtn = form.querySelector("[data-profile-save]");
    const resetBtn = form.querySelector("[data-profile-reset]");
    const statusEl = form.querySelector("[data-profile-status]");

    let saved = readValues();

    function readValues() {
        return Object.fromEntries(
            FIELDS.map((name) => [name, field(name).value.trim()]),
        );
    }

    const isDirty = () =>
        FIELDS.some((name) => field(name).value.trim() !== saved[name]);

    function syncButtons() {
        const dirty = isDirty();
        saveBtn.disabled = !dirty;
        resetBtn.disabled = !dirty;
    }

    function showStatus(message, type) {
        statusEl.textContent = message;
        statusEl.classList.toggle("is-success", type === "success");
        statusEl.classList.toggle("is-error", type === "error");
        statusEl.classList.remove("d-none");
    }

    function clearStatus() {
        statusEl.textContent = "";
        statusEl.classList.add("d-none", "is-success", "is-error");
        statusEl.classList.remove("is-success", "is-error");
    }

    function clearFieldErrors() {
        FIELDS.forEach((name) =>
            setFieldState(
                field(name),
                form.querySelector(`[data-field-error="${name}"]`),
                "",
            ),
        );
    }

    const checks = () => [
        [
            field("first_name"),
            getNameError(field("first_name").value, "First name"),
        ],
        [
            field("last_name"),
            getNameError(field("last_name").value, "Last name"),
        ],
        [field("phone"), phoneError(field("phone").value)],
    ];

    // Live feedback: names on blur, then as you type; phone from the first keystroke.
    wireLiveField(form, "first_name", (v) => getNameError(v, "First name"));
    wireLiveField(form, "last_name", (v) => getNameError(v, "Last name"));
    wireLiveField(form, "phone", phoneError);

    // Block characters that can never be valid (pasted text is still caught by the checks).
    const filter = (name, pattern) =>
        field(name).addEventListener("input", (event) => {
            event.target.value = event.target.value.replace(pattern, "");
        });
    filter("first_name", /[^A-Za-z\s]/g);
    filter("last_name", /[^A-Za-z\s]/g);
    filter("phone", /\D/g);

    form.addEventListener("input", () => {
        clearStatus();
        syncButtons();
    });

    resetBtn.addEventListener("click", () => {
        FIELDS.forEach((name) => (field(name).value = saved[name]));
        clearFieldErrors();
        clearStatus();
        syncButtons();
    });

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearStatus();

        const failure = checks().find(([, message]) => message);
        if (failure) {
            const [input, message] = failure;
            setFieldState(
                input,
                form.querySelector(`[data-field-error="${input.name}"]`),
                message,
            );
            input.focus();
            return;
        }

        saveBtn.disabled = true;
        const payload = readValues();

        try {
            const response = await fetch(form.action, {
                method: "PATCH",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(form),
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const errors = data.errors ?? {};
                Object.entries(errors).forEach(([name, messages]) => {
                    const input = field(name);
                    if (input)
                        setFieldState(
                            input,
                            form.querySelector(`[data-field-error="${name}"]`),
                            messages[0],
                        );
                });
                showStatus(
                    data.message ||
                        "We couldn't save your profile. Please review the fields and try again.",
                    "error",
                );
                return;
            }

            saved = payload;
            updateSummary(payload);
            showStatus(
                data.message || "Your profile has been updated.",
                "success",
            );
        } catch {
            showStatus(
                "Something went wrong on our end. Please check your connection and try again.",
                "error",
            );
        } finally {
            syncButtons();
        }
    });
}

// Keep the summary card in step with what was just saved.
function updateSummary({ first_name, last_name }) {
    const name = `${first_name} ${last_name}`.trim();
    const nameEl = document.querySelector("[data-profile-name]");
    const initialsEl = document.querySelector("[data-profile-initials]");
    if (nameEl) nameEl.textContent = name || "Your name";
    if (initialsEl)
        initialsEl.textContent =
            `${first_name.charAt(0)}${last_name.charAt(0)}`.toUpperCase() ||
            "U";
}

document.addEventListener("DOMContentLoaded", initProfile);
