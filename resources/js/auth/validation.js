/**
 * Shared client-side validation helpers.
 * Single source of truth for the email/phone/password regexes,
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

// Auth identifier is email-only. isValidPhone stays below for the booking
// flow's phone fields (getPhoneError), which are unaffected.
function isValidIdentifier(value) {
    return isValidEmail(value.trim());
}

function isValidPassword(value) {
    return PASSWORD_REGEX.test(value);
}

/*
 Returns '' if the identifier (email) is valid, or a message naming exactly what's wrong with it. The auth modal's identifier field is email-only; phone validation lives on in getPhoneError / getContactNumberError for the booking flow and is not used here.
 */
function getIdentifierError(value) {
    const v = value.trim();
    if (!v) return "Enter your email address.";

    if (!v.includes("@"))
        return "Email must include an @ symbol, e.g. name@example.com.";
    if (!/^[^\s@]+@/.test(v)) return "Enter the part before the @ symbol.";
    if (!/@[^\s@]+\./.test(v))
        return "Email is missing a domain, e.g. name@example.com.";
    return isValidEmail(v)
        ? ""
        : "That doesn't look like a valid email address.";
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

/*
 Same contract as wireLiveField, but for fields that should give feedback
 on the very first keystroke instead of waiting for a first blur — e.g.
 a field where mistakes are cheap to point out immediately (contact
 details typed in the booking flow). errorFn still receives the raw
 value, so an errorFn that treats an empty value as invalid (a required
 field) will only ever surface that once the field has been touched,
 since 'input' cannot fire on an untouched field.
 */
function wireLiveFieldImmediate(
    form,
    fieldName,
    errorFn,
    { allowEmpty = false, isEmpty = (v) => !v.trim() } = {},
) {
    const input = form.querySelector(`[name="${fieldName}"]`);
    const errorEl = form.querySelector(`[data-field-error="${fieldName}"]`);
    if (!input) return;

    input.addEventListener("input", () => {
        const message =
            allowEmpty && isEmpty(input.value) ? "" : errorFn(input.value);
        setFieldState(input, errorEl, message);
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

/*
 Used by each auth form's banner-level clearError(). That function runs on
 every 'input' in the form (the listener sits on the modal, so it fires
 "after" the field's own live-validation listener) and used to strip
 .is-invalid from every input — which would wipe the red border that
 wireLiveFieldImmediate had just set while the user is still typing. This
 clears only inputs with no visible per-field message (e.g. ones flagged by
 a server error banner), and leaves live-validated fields to their own
 listener.
 */
function clearInvalidWithoutFieldError(form) {
    form.querySelectorAll("input").forEach((input) => {
        const errorEl = form.querySelector(
            `[data-field-error="${input.name}"]`,
        );
        if (errorEl && !errorEl.classList.contains("d-none")) return;
        input.classList.remove("is-invalid");
    });
}

// Full reset (modal closed / form reset): clears every input's border AND
// its per-field message text.
function clearAllFieldStates(form) {
    form.querySelectorAll("input").forEach((input) => {
        clearFieldState(
            input,
            form.querySelector(`[data-field-error="${input.name}"]`),
        );
    });
}

/*
 Single-purpose counterparts to getIdentifierError, for forms that have a dedicated Email field and a dedicated Contact Number field rather than one
 combined identifier field.
 */
function getEmailError(value) {
    const v = value.trim();
    if (!v) return "Enter your email address.";
    if (!/^[^\s@]+@/.test(v)) return "Enter the part before the @ symbol.";
    if (!/@[^\s@]+\./.test(v))
        return "Email is missing a domain, e.g. name@example.com.";
    return isValidEmail(v)
        ? ""
        : "That doesn't look like a valid email address.";
}

function getPhoneError(value) {
    const v = value.trim();
    if (!v) return "Enter your contact number.";
    const digits = v.replace(/\D/g, "");
    if (!PHONE_CHARS_REGEX.test(v)) return "Enter a valid phone number.";
    if (digits.length !== PHONE_DIGITS)
        return `Phone number must be exactly ${PHONE_DIGITS} digits (${digits.length} entered).`;
    return isValidPhone(v) ? "" : "Enter a valid phone number.";
}

/*
 Stricter counterpart to getPhoneError for the booking flow's own Contact
 Number / Venue Contact Person fields, which are PH mobile numbers
 specifically: on top of everything getPhoneError already checks, the
 digits must start with "09" (e.g. 0917xxxxxxx). isValidPhone() isn't
 reused here since it has no notion of a required prefix; kept as a
 separate function rather than changing getPhoneError/isValidPhone so the
 general-purpose getPhoneError is unaffected. No "Enter a valid phone number" character-format
 fallback here — these fields are digit-filtered on input (see
 initDigitsOnlyFilter in client-information.js/event-information.js), so
 non-digit characters can never reach this validator and that message
 could never actually fire.
 */
function getContactNumberError(value) {
    const v = value.trim();
    if (!v) return "Enter a contact number.";
    const digits = v.replace(/\D/g, "");
    if (!digits.startsWith("09"))
        return "Contact number must start with 09, e.g. 09XXXXXXXXX.";
    if (digits.length !== PHONE_DIGITS)
        return `Phone number must be exactly ${PHONE_DIGITS} digits (${digits.length} entered).`;
    return "";
}

const CONTACT_NUMBER_PREFIX = "09";
// A field holding only the un-typed-into prefix reads as "empty" for
// required/optional purposes — the user hasn't actually entered anything.
const isContactNumberUntouched = (value) =>
    !value.trim() || value.trim() === CONTACT_NUMBER_PREFIX;

/*
 Keeps a Contact Number-style field always starting with "09" and digits
 only, capped at PHONE_DIGITS total — call on every 'input' event.
 Handles the common edits: typing more digits after the prefix, pasting a
 full number with or without the leading 0, and backspacing into the
 prefix itself (re-derives "09" instead of leaving a broken prefix).
 */
function normalizeContactNumberInput(value) {
    let digits = value.replace(/\D/g, "");
    if (!digits.startsWith(CONTACT_NUMBER_PREFIX)) {
        digits = digits.startsWith("9")
            ? `0${digits}`
            : `${CONTACT_NUMBER_PREFIX}${digits.replace(/^0+/, "")}`;
    }
    return digits.slice(0, PHONE_DIGITS);
}

const NAME_REGEX = /^[A-Za-z\s]+$/;

/*
 For First Name / Last Name in the booking flow: required, and letters
 (plus spaces, for names written as two words) only — no digits or other
 symbols.
 */
function getNameError(value, label) {
    const v = value.trim();
    if (!v) return `${label} is required.`;
    if (!NAME_REGEX.test(v)) return `${label} can only contain letters.`;
    return "";
}

export {
    EMAIL_REGEX,
    PASSWORD_REGEX,
    PHONE_DIGITS,
    NAME_REGEX,
    isValidEmail,
    isValidPhone,
    isValidIdentifier,
    isValidPassword,
    getIdentifierError,
    getPasswordError,
    getRequiredError,
    getEmailError,
    getPhoneError,
    getContactNumberError,
    normalizeContactNumberInput,
    isContactNumberUntouched,
    getNameError,
    getMatchError,
    wireLiveField,
    wireLiveFieldImmediate,
    setFieldState,
    clearFieldState,
    clearInvalidWithoutFieldError,
    clearAllFieldStates,
};
