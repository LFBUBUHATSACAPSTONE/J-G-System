/**
 * Account > Security: change-password form. FRONT END ONLY. Same pattern as profile.js: novalidate on
 * the form, every check enforced here, fetch() on submit, an inline message under each field.
 * The password rule is getPasswordError() from auth/validation.js, the same one the sign-up form uses.
 *
 * Nothing is sent until the backend adds the route: while the form's `action` is empty
 * (`user.account.password.update` does not exist) submit only shows a notice.
 *
 * Back-end contract for `user.account.password.update` (PATCH, JSON body
 * {current_password, password, password_confirmation}):
 *   200 {"ok": true, "message": "…"}                    -> saved, the form is cleared
 *   422 {"message": "…", "errors": {"current_password": ["…"], "password": ["…"]}}
 *       (wrong current password, rule not met, same as the old password)
 *   401 / 403 {"message": "…"}                          -> shown above the button
 * The server must enforce every rule below again; this file is only the first line of feedback.
 */

import { getCsrfToken } from "../auth/csrf.js";
import {
    getPasswordError,
    getMatchError,
    getRequiredError,
    wireLiveField,
    setFieldState,
} from "../auth/validation.js";

const FORM_ID = "account-password-form";
const FIELDS = ["current_password", "password", "password_confirmation"];

// One test per line of the checklist; together they equal getPasswordError() === "".
const RULES = {
    length: (v) => v.length >= 8,
    lower: (v) => /[a-z]/.test(v),
    upper: (v) => /[A-Z]/.test(v),
    number: (v) => /\d/.test(v),
    special: (v) => /[^A-Za-z0-9]/.test(v),
};

function initSecurity() {
    const form = document.getElementById(FORM_ID);
    if (!form) return;

    const field = (name) => form.querySelector(`[name="${name}"]`);
    const errorEl = (name) =>
        form.querySelector(`[data-field-error="${name}"]`);
    const saveBtn = form.querySelector("[data-password-save]");
    const statusEl = form.querySelector("[data-password-status]");
    const current = field("current_password");
    const next = field("password");
    const confirm = field("password_confirmation");

    // Show / hide each password field.
    form.querySelectorAll("[data-password-toggle]").forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        button.addEventListener("click", () => {
            const show = input.type === "password";
            input.type = show ? "text" : "password";
            button.setAttribute("aria-pressed", String(show));
            button.setAttribute(
                "aria-label",
                show ? "Hide password" : "Show password",
            );
            button.querySelector("i").className =
                `ph ph-${show ? "eye-slash" : "eye"}`;
        });
    });

    const currentError = (v) => getRequiredError(v, "Current password");
    const newError = (v) => {
        const message = getPasswordError(v);
        if (message) return message;
        return v === current.value
            ? "Choose a password different from your current one."
            : "";
    };
    const confirmError = (v) => getMatchError(v, next.value);

    const checks = [
        [current, currentError],
        [next, newError],
        [confirm, confirmError],
    ];

    wireLiveField(form, "current_password", currentError);
    wireLiveField(form, "password", newError);
    wireLiveField(form, "password_confirmation", confirmError);

    function syncRules() {
        form.querySelectorAll("[data-password-rule]").forEach((item) => {
            const met = RULES[item.dataset.passwordRule](next.value);
            item.classList.toggle("is-met", met);
            item.querySelector("i").className =
                `ph ph-${met ? "check-circle" : "circle"}`;
        });
    }

    const isValid = () => checks.every(([input, fn]) => !fn(input.value));
    const syncButton = () => (saveBtn.disabled = !isValid());

    function showStatus(message, type) {
        statusEl.textContent = message;
        statusEl.classList.toggle("is-success", type === "success");
        statusEl.classList.toggle("is-error", type === "error");
        statusEl.classList.remove("d-none");
    }

    function clearStatus() {
        statusEl.textContent = "";
        statusEl.classList.add("d-none");
        statusEl.classList.remove("is-success", "is-error");
    }

    form.addEventListener("input", () => {
        // A message that is already on screen follows the value (the match message depends on the new
        // password, the "same as current" one on the current password); nothing new appears while typing.
        const shown = (name) => !errorEl(name).classList.contains("d-none");
        if (shown("password"))
            setFieldState(next, errorEl("password"), newError(next.value));
        if (shown("password_confirmation"))
            setFieldState(
                confirm,
                errorEl("password_confirmation"),
                confirmError(confirm.value),
            );
        clearStatus();
        syncRules();
        syncButton();
    });

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearStatus();

        const failure = checks.find(([input, fn]) => fn(input.value));
        if (failure) {
            const [input, fn] = failure;
            setFieldState(input, errorEl(input.name), fn(input.value));
            input.focus();
            return;
        }

        if (!form.getAttribute("action")) {
            showStatus(
                "Changing passwords isn't connected yet, so nothing was changed.",
                "error",
            );
            return;
        }

        saveBtn.disabled = true;
        const payload = Object.fromEntries(
            FIELDS.map((name) => [name, field(name).value]),
        );

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
                    if (input) setFieldState(input, errorEl(name), messages[0]);
                });
                showStatus(
                    data.message ||
                        "We couldn't change your password. Please review the fields and try again.",
                    "error",
                );
                return;
            }

            // Saved: empty the form (never keep passwords in the page) and reset every message.
            FIELDS.forEach((name) => {
                field(name).value = "";
                setFieldState(field(name), errorEl(name), "");
            });
            syncRules();
            showStatus(
                data.message || "Your password has been updated.",
                "success",
            );
        } catch {
            showStatus(
                "Something went wrong on our end. Please check your connection and try again.",
                "error",
            );
        } finally {
            syncButton();
        }
    });
}

document.addEventListener("DOMContentLoaded", initSecurity);
