/**
 * Shared client-side validation helpers.
 * Single source of truth for the identifier/email/phone/password regexes,
 * and for building error messages that explain *what* is wrong rather than
 * a generic "invalid" message.
 */

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// At least 1 lowercase, 1 uppercase, 1 digit, 1 special char, 8+ chars total.
const PASSWORD_REGEX =
    /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

const PHONE_CHARS_REGEX = /^\+?[\d\s()-]+$/;
const PHONE_DIGITS = 11;

function isValidEmail(value) {
    return EMAIL_REGEX.test(value.trim());
}

function isValidPhone(value) {
    const digits = value.replace(/\D/g, "");
    return PHONE_CHARS_REGEX.test(value) && digits.length === PHONE_DIGITS;
}

function isValidIdentifier(value) {
    const v = value.trim();
    return isValidEmail(v) || isValidPhone(v);
}

function isValidPassword(value) {
    return PASSWORD_REGEX.test(value);
}

/*
 Returns '' if the identifier is valid, or a message naming exactly what's wrong with it. Branches on what the user *appears* to be typing (email vs phone) so the message matches their intent instead of listing both formats every time.
 */
function getIdentifierError(value) {
    const v = value.trim();
    if (!v) return "Enter your email address or phone number.";

    if (v.includes("@")) {
        if (!/^[^\s@]+@/.test(v)) return "Enter the part before the @ symbol.";
        if (!/@[^\s@]+\./.test(v))
            return "Email is missing a domain, e.g. name@example.com.";
        return isValidEmail(v)
            ? ""
            : "That doesn't look like a valid email address.";
    }

    const looksLikePhone = /^[\d\s()+-]+$/.test(v);
    if (looksLikePhone) {
        const digits = v.replace(/\D/g, "");
        if (digits.length < PHONE_DIGITS)
            return `Phone number must be exactly ${PHONE_DIGITS} digits (${digits.length} entered).`;
        if (digits.length > PHONE_DIGITS)
            return `Phone number must be exactly ${PHONE_DIGITS} digits (${digits.length} entered).`;
        return isValidPhone(v) ? "" : "Enter a valid phone number.";
    }

    return "Enter a valid email address or phone number.";
}

/*
 Returns '' if the password meets all requirements, or a message listing only the requirement(s) still unmet.
 */
function getPasswordError(value) {
    if (!value) return "Enter a password.";

    const missing = [];
    if (value.length < 8) missing.push("be at least 8 characters");
    if (!/[a-z]/.test(value)) missing.push("include a lowercase letter");
    if (!/[A-Z]/.test(value)) missing.push("include an uppercase letter");
    if (!/\d/.test(value)) missing.push("include a number");
    if (!/[^A-Za-z0-9]/.test(value))
        missing.push("include a special character");

    if (missing.length === 0) return "";
    if (missing.length === 1) return `Password must ${missing[0]}.`;

    const last = missing.pop();
    return `Password must ${missing.join(", ")}, and ${last}.`;
}

function getRequiredError(value, label) {
    return value.trim() ? "" : `${label} is required.`;
}

function getMatchError(value, otherValue) {
    if (!value) return "Confirm your password.";
    return value === otherValue ? "" : "Passwords do not match.";
}

/*
 Wires a single field for live feedback:
   - `errorFn(value)` returns '' when valid, or a specific message when not
   - checks on 'blur' (first time the field is left) and on every 'input' after that first blur, so the user isn't scolded before they've finished typing anything
   - toggles .is-invalid and a per-field <small data-field-error="NAME">
 */
function wireLiveField(form, fieldName, errorFn) {
    const input = form.querySelector(`[name="${fieldName}"]`);
    const errorEl = form.querySelector(`[data-field-error="${fieldName}"]`);
    if (!input) return;

    let touched = false;

    const run = () => {
        const message = errorFn(input.value);
        setFieldState(input, errorEl, message);
    };

    input.addEventListener("blur", () => {
        touched = true;
        run();
    });

    input.addEventListener("input", () => {
        if (touched) run();
    });
}

function setFieldState(input, errorEl, message) {
    const valid = !message;
    input.classList.toggle("is-invalid", !valid);
    if (!errorEl) return;
    errorEl.textContent = message || "";
    errorEl.classList.toggle("d-none", valid);
}

function clearFieldState(input, errorEl) {
    input.classList.remove("is-invalid");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
}

export {
    EMAIL_REGEX,
    PASSWORD_REGEX,
    isValidEmail,
    isValidPhone,
    isValidIdentifier,
    isValidPassword,
    getIdentifierError,
    getPasswordError,
    getRequiredError,
    getMatchError,
    wireLiveField,
    setFieldState,
    clearFieldState,
};
