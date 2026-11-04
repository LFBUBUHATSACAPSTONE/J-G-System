/**
 * Admin Verification Code UX. Same behaviour as auth/verification-code.js, scoped to the sign-in
 * card instead of #authModal:
 *  - digits only, auto-advance, Backspace to previous box, ArrowLeft / ArrowRight, paste-to-fill
 *  - Resend Code with a 30 s cooldown (the AJAX call lives in resend-code.js, fired through the
 *    same bubbling `auth:resend-code` CustomEvent)
 *
 * Back-end contract for `admin.verification.confirm` (POST JSON {code, context, verification_token}):
 *   200 {token, email}  -> code valid; both go to the new-password step
 *   422 {message}       -> code invalid / expired
 *   429 {message}       -> too many attempts
 * `context` is always "reset": admin accounts are created by the owner, so there is no sign-up.
 */

import { getCsrfToken } from "../auth/csrf.js";
import { setView, focusFirstField } from "./views.js";
import { setResetCredentials } from "./new-password.js";

const BOX_SELECTOR = ".admin-login__code-box";
const RESEND_BTN_ID = "admin-resend-code-btn";
const RESEND_COOLDOWN_SECONDS = 30;
const CODE_LENGTH = 6;
const CONTEXT = "reset";

let cooldownTimer = null;
let verificationToken = "";
let verificationEmail = "";

// `email` is the address typed in the forgot-password step; the new-password step falls back to it
// when confirm does not return one.
export function setVerificationToken(token, email) {
    verificationToken = token || "";
    if (email !== undefined) verificationEmail = email;
}

export function setVerificationDestination(maskedText) {
    document
        .querySelectorAll("[data-verification-destination]")
        .forEach((el) => {
            el.textContent = maskedText;
        });
}

export function initVerificationCode(root) {
    const form = root.querySelector("[data-admin-verification]");
    if (!form) return;

    form.addEventListener("input", (event) => {
        const box = event.target.closest(BOX_SELECTOR);
        if (!box) return;
        clearVerificationError(form);
        handleInput(box);
    });

    form.addEventListener("keydown", (event) => {
        const box = event.target.closest(BOX_SELECTOR);
        if (box) handleKeydown(event, box);
    });

    form.addEventListener("paste", (event) => {
        const box = event.target.closest(BOX_SELECTOR);
        if (!box) return;
        event.preventDefault();
        fillBoxes(box, event.clipboardData?.getData("text") ?? "");
    });

    form.addEventListener("focusin", (event) => {
        event.target.closest(BOX_SELECTOR)?.select();
    });

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitVerificationCode(root, form);
    });

    form.addEventListener("click", (event) => {
        const btn = event.target.closest(`#${RESEND_BTN_ID}`);
        if (!btn || btn.disabled) return;
        btn.dispatchEvent(
            new CustomEvent("auth:resend-code", {
                bubbles: true,
                detail: { context: CONTEXT, verificationToken },
            }),
        );
        startResendCooldown();
    });
}

function getBoxes(form) {
    return Array.from(form.querySelectorAll(BOX_SELECTOR));
}

function handleInput(box) {
    const digits = box.value.replace(/\D/g, "");
    if (digits.length > 1) return fillBoxes(box, digits);

    box.value = digits;
    if (!digits) return;
    const boxes = getBoxes(box.closest("form"));
    boxes[boxes.indexOf(box) + 1]?.focus();
}

function handleKeydown(event, box) {
    const boxes = getBoxes(box.closest("form"));
    const index = boxes.indexOf(box);

    if (event.key === "Backspace" && !box.value && index > 0) {
        event.preventDefault();
        boxes[index - 1].value = "";
        boxes[index - 1].focus();
    } else if (event.key === "ArrowLeft" && index > 0) {
        event.preventDefault();
        boxes[index - 1].focus();
    } else if (event.key === "ArrowRight" && index < boxes.length - 1) {
        event.preventDefault();
        boxes[index + 1].focus();
    }
}

function fillBoxes(startBox, text) {
    const digits = text.replace(/\D/g, "");
    if (!digits) return;

    const boxes = getBoxes(startBox.closest("form"));
    const start = boxes.indexOf(startBox);
    for (let i = 0; i < digits.length && start + i < boxes.length; i++) {
        boxes[start + i].value = digits[i];
    }
    boxes[Math.min(start + digits.length, boxes.length - 1)].focus();
}

function getVerificationCode(form) {
    return getBoxes(form)
        .map((b) => b.value)
        .join("");
}

async function submitVerificationCode(root, form) {
    const code = getVerificationCode(form);
    if (code.length < CODE_LENGTH) {
        showVerificationError(form, "Please enter the 6-digit code.");
        return;
    }

    const submitBtn = form.querySelector('[type="submit"]');
    clearVerificationError(form);
    submitBtn.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(form),
            },
            body: JSON.stringify({
                code,
                context: CONTEXT,
                verification_token: verificationToken,
            }),
        });
        const data = await response.json().catch(() => ({}));

        if (response.ok) {
            setResetCredentials({
                token: data.token,
                email: data.email || verificationEmail,
            });
            resetVerificationCode(root);
            setView(root, "new-password");
            focusFirstField(root);
            return;
        }

        showVerificationError(
            form,
            data.message ||
                (response.status === 429
                    ? "Too many attempts. Please wait a moment and try again."
                    : "Invalid verification code. Please try again."),
        );
        getBoxes(form)[0]?.focus();
    } catch {
        showVerificationError(form, "Something went wrong. Please try again.");
    } finally {
        submitBtn.disabled = false;
    }
}

function showVerificationError(form, message) {
    const errorEl = form.querySelector("[data-verification-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
    getBoxes(form).forEach((b) => b.classList.add("is-invalid"));
}

function clearVerificationError(form) {
    const errorEl = form?.querySelector("[data-verification-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    getBoxes(form).forEach((b) => b.classList.remove("is-invalid"));
}

export function startResendCooldown(seconds = RESEND_COOLDOWN_SECONDS) {
    const btn = document.getElementById(RESEND_BTN_ID);
    if (!btn) return;

    clearInterval(cooldownTimer);
    let remaining = seconds;
    btn.disabled = true;
    btn.textContent = `Resend Code (${remaining}s)`;

    cooldownTimer = setInterval(() => {
        remaining -= 1;
        if (remaining <= 0) {
            clearInterval(cooldownTimer);
            btn.disabled = false;
            btn.textContent = "Resend Code";
            return;
        }
        btn.textContent = `Resend Code (${remaining}s)`;
    }, 1000);
}

export function resetVerificationCode(root) {
    verificationToken = "";
    const form = root.querySelector("[data-admin-verification]");
    if (form) {
        getBoxes(form).forEach((b) => {
            b.value = "";
        });
        clearVerificationError(form);
    }
    const btn = document.getElementById(RESEND_BTN_ID);
    if (btn) {
        clearInterval(cooldownTimer);
        btn.disabled = false;
        btn.textContent = "Resend Code";
    }
}
