/**
 * Verification Code UX
 *
 * Behavior for the 6-box code input in components/auth/verification-form.blade.php:
 *  - digits only, auto-advance on input
 *  - Backspace on an empty box clears and moves to the previous box
 *  - ArrowLeft / ArrowRight navigation
 *  - paste-to-fill (also covers multi-character autofill)
 *  - Resend Code cooldown (button disabled + countdown label)
 *
 * Markup contract:
 *  - .auth-modal__code-box (x6) inside #authModal
 *  - #resend-code-btn
 *  - [data-verification-destination]
 *
 * Confirm: submits { code, context, verification_token } via fetch (JSON).
 * `context` is "reset" (default) or "signup", set by the caller through
 * setVerificationContext(). `verification_token` is issued by register /
 * password.email and set through setVerificationToken().
 * On success: "reset" moves to the new-password view, "signup" moves to login.
 * On failure an error is shown under the boxes.
 *
 * Expected back-end contract for `verification.confirm`:
 *   200 {"token":"…","email":"…"}
 * -> code valid (reset context; token/email go to new-password)
 *   200 (any JSON)
 * -> code valid (signup context; body ignored)
 *   422 {"message":"…"}
 * -> code invalid (message optional)
 *
 * The Resend AJAX call is back-end dependent: this module only dispatches
 * a bubbling `auth:resend-code` CustomEvent from the button, carrying
 * { context, verificationToken }.
 */

import { setAuthView } from "../auth-modal.js";
import { setResetCredentials } from "./new-password.js";

const AUTH_MODAL_ID = "authModal";
const BOX_SELECTOR = ".auth-modal__code-box";
const RESEND_BTN_ID = "resend-code-btn";
const RESEND_COOLDOWN_SECONDS = 30;
const CODE_LENGTH = 6;
const DEFAULT_CONTEXT = "reset";

let cooldownTimer = null;
let verificationContext = DEFAULT_CONTEXT;
let verificationToken = "";

function setVerificationContext(context) {
    verificationContext = context;
}

function setVerificationToken(token) {
    verificationToken = token || "";
    document
        .querySelectorAll('input[name="verification_token"]')
        .forEach((input) => {
            input.value = verificationToken;
        });
}

function initVerificationCode() {
    const modalEl = document.getElementById(AUTH_MODAL_ID);
    if (!modalEl) return;

    modalEl.addEventListener("input", (event) => {
        const box = event.target.closest(BOX_SELECTOR);
        if (!box) return;
        clearVerificationError(box.closest("form"));
        handleInput(box);
    });

    modalEl.addEventListener("keydown", (event) => {
        const box = event.target.closest(BOX_SELECTOR);
        if (!box) return;
        handleKeydown(event, box);
    });

    modalEl.addEventListener("paste", (event) => {
        const box = event.target.closest(BOX_SELECTOR);
        if (!box) return;
        event.preventDefault();
        const text = event.clipboardData?.getData("text") ?? "";
        fillBoxes(box, text);
    });

    modalEl.addEventListener("submit", (event) => {
        const form = event.target;
        if (!form.querySelector(BOX_SELECTOR)) return;
        event.preventDefault();
        submitVerificationCode(modalEl, form);
    });

    modalEl.addEventListener("focusin", (event) => {
        const box = event.target.closest(BOX_SELECTOR);
        if (box) box.select();
    });

    modalEl.addEventListener("click", (event) => {
        const btn = event.target.closest(`#${RESEND_BTN_ID}`);
        if (!btn || btn.disabled) return;

        btn.dispatchEvent(
            new CustomEvent("auth:resend-code", {
                bubbles: true,
                detail: { context: verificationContext, verificationToken },
            }),
        );
        startResendCooldown();
    });

    modalEl.addEventListener("hidden.bs.modal", resetVerificationCode);
}

function getBoxes(fromBox) {
    return Array.from(fromBox.closest("form").querySelectorAll(BOX_SELECTOR));
}

function handleInput(box) {
    const digits = box.value.replace(/\D/g, "");
    if (digits.length > 1) {
        fillBoxes(box, digits);
        return;
    }

    box.value = digits;
    if (!digits) return;

    const boxes = getBoxes(box);
    const next = boxes[boxes.indexOf(box) + 1];
    if (next) next.focus();
}

function handleKeydown(event, box) {
    const boxes = getBoxes(box);
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

// Writes digits into consecutive boxes starting at `startBox`.
function fillBoxes(startBox, text) {
    const digits = text.replace(/\D/g, "");
    if (!digits) return;

    const boxes = getBoxes(startBox);
    const start = boxes.indexOf(startBox);

    for (let i = 0; i < digits.length && start + i < boxes.length; i++) {
        boxes[start + i].value = digits[i];
    }

    boxes[Math.min(start + digits.length, boxes.length - 1)].focus();
}

const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || "";

async function submitVerificationCode(modalEl, form) {
    const code = getVerificationCode(form);
    if (code.length < CODE_LENGTH) {
        showVerificationError(form, "Please enter the 6-digit code.");
        return;
    }

    const submitBtn = form.querySelector('[type="submit"]');
    const hiddenTokenInput = form.querySelector('input[name="verification_token"]');
    if (hiddenTokenInput) {
        hiddenTokenInput.value = verificationToken;
    }
    clearVerificationError(form);
    submitBtn.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "X-XSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                code,
                context: verificationContext,
                verification_token: verificationToken,
            }),
        });

        if (response.ok) {
            if (verificationContext === "signup") {
                setAuthView(modalEl, "login");
                return;
            }

            const data = await response.json().catch(() => ({}));
            setResetCredentials({ token: data.token, email: data.email });
            setAuthView(modalEl, "new-password");
            return;
        }

        const data = await response.json().catch(() => ({}));
        showVerificationError(
            form,
            data.message || "Invalid verification code. Please try again.",
        );
        form.querySelector(BOX_SELECTOR).focus();
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
    form.querySelectorAll(BOX_SELECTOR).forEach((b) =>
        b.classList.add("is-invalid"),
    );
}

function clearVerificationError(form) {
    const errorEl = form?.querySelector("[data-verification-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    form?.querySelectorAll(BOX_SELECTOR).forEach((b) =>
        b.classList.remove("is-invalid"),
    );
}

function getVerificationCode(form) {
    return Array.from(form.querySelectorAll(BOX_SELECTOR))
        .map((b) => b.value)
        .join("");
}

function setVerificationDestination(maskedText) {
    document
        .querySelectorAll("[data-verification-destination]")
        .forEach((el) => {
            el.textContent = maskedText;
        });
}

function startResendCooldown(seconds = RESEND_COOLDOWN_SECONDS) {
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

function resetVerificationCode() {
    verificationContext = DEFAULT_CONTEXT;
    verificationToken = "";

    document.querySelectorAll(BOX_SELECTOR).forEach((b) => {
        b.value = "";
    });
    document
        .querySelectorAll("[data-verification-error]")
        .forEach((el) => clearVerificationError(el.closest("form")));

    const btn = document.getElementById(RESEND_BTN_ID);
    if (btn) {
        clearInterval(cooldownTimer);
        btn.disabled = false;
        btn.textContent = "Resend Code";
    }
}

document.addEventListener("DOMContentLoaded", initVerificationCode);

export {
    initVerificationCode,
    getVerificationCode,
    setVerificationContext,
    setVerificationToken,
    setVerificationDestination,
    startResendCooldown,
    resetVerificationCode,
};