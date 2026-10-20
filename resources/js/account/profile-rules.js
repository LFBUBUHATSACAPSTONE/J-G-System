/**
 * One place for the profile rules, filters and display helpers used by BOTH the admin account page
 * (js/admin/account.js) and the customer Profile page (js/account/profile.js). The wording comes from
 * js/auth/validation.js (the sign-up rules); nothing is copied from it.
 *
 * Front end only. The server still validates; its 422 messages are shown as they come.
 */
import {
    getIdentifierError,
    getNameError,
    getPasswordError,
    getMatchError,
    getRequiredError,
    getContactNumberError,
} from "../auth/validation.js";

// ---------- password checklist ----------
// One test per line of the checklist; together they equal getPasswordError() === "".
export const PASSWORD_CHECKS = {
    length: (v) => v.length >= 8,
    lower: (v) => /[a-z]/.test(v),
    upper: (v) => /[A-Z]/.test(v),
    number: (v) => /\d/.test(v),
    special: (v) => /[^A-Za-z0-9]/.test(v),
};

/** Ticks the checklist items inside `scope` for the given password. */
export function syncPasswordChecklist(scope, value) {
    scope.querySelectorAll("[data-password-rule]").forEach((item) => {
        const met = PASSWORD_CHECKS[item.dataset.passwordRule](value);
        item.classList.toggle("is-met", met);
        item.querySelector("i").className =
            `ph ph-${met ? "check-circle" : "circle"}`;
    });
}

/** Show / hide buttons (`data-password-toggle="<input id>"`). */
export function wirePasswordToggles(scope) {
    scope.querySelectorAll("[data-password-toggle]").forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;
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
}

// ---------- field rules (return "" when valid) ----------
export const rules = {
    first_name: (v) => getNameError(v, "First name"),
    last_name: (v) => getNameError(v, "Last name"),
    middle_initial: (v) =>
        v && !/^[A-Za-z]$/.test(v.trim())
            ? "Middle initial must be a single letter."
            : "",
    email: getIdentifierError, // same rule and messages as the auth modal's email field
    /** Contact number that must be given. Same rule and messages as the booking form's Contact Number. */
    phone: getContactNumberError,
    /** Same rule, but the field may stay empty. Once something is typed it must be a full number. */
    phoneOptional: (v) => (v.trim() ? getContactNumberError(v) : ""),
    /** Free text, optional. */
    address: () => "",
    current_password: (v) => getRequiredError(v, "Current password"),
};

/**
 * Password rules that depend on a sibling field.
 * `get` returns the current value of another field by name.
 */
export function passwordRules(get) {
    return {
        password: (v) => {
            // Nothing typed in either box: the section is open but not being used yet.
            if (!v && !get("password_confirmation")) return "";
            const message = getPasswordError(v);
            if (message) return message;
            return v === get("current_password")
                ? "Choose a password different from your current one."
                : "";
        },
        password_confirmation: (v) =>
            !v && !get("password") ? "" : getMatchError(v, get("password")),
    };
}

// ---------- filters while typing (return the cleaned value) ----------
export const filters = {
    first_name: (v) => v.replace(/[^A-Za-z\s]/g, ""),
    last_name: (v) => v.replace(/[^A-Za-z\s]/g, ""),
    middle_initial: (v) => v.toUpperCase().replace(/[^A-Z]/g, ""),
    // Digits only, like the booking form's Contact Number: nothing is inserted for the user, and the
    // validator (09 prefix, exactly 11 digits) reports a number that is too short or too long.
    phone: (v) => v.replace(/\D/g, ""),
};

// ---------- display ----------
/** 09123456789 -> 0912 345 6789 */
export const formatPhone = (digits) =>
    /^\d{11}$/.test(digits)
        ? digits.replace(/^(\d{4})(\d{3})(\d{4})$/, "$1 $2 $3")
        : digits;
