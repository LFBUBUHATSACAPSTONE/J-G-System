/**
 * Booking Summary + Payment step (booking flow). This step has no
 * editable recap fields — those <input readonly> elements are display
 * only — so there's no live per-field validation like the earlier
 * steps. What this file owns:
 *
 *   - a two-option toggle (data-payment-option-group / data-payment-option)
 *     for Full Payment vs Down Payment, same role="radio" +
 *     aria-checked pattern as event-information's venue-type checkboxes,
 *     writing the chosen value into the hidden
 *     input[data-payment-option-input] that gets submitted with the form.
 *   - Down Payment reveals a numbers-only amount input
 *     (input[data-down-payment-input]). The amount must be at least 30%
 *     of the package cost, read from form[data-package-cost] (the
 *     controller's $packageCost). A live "% of package cost" readout sits
 *     under the field. It is checked live on every keystroke:
 *     below that, an inline error says so (and again on submit, where
 *     nothing is sent). Full Payment hides and clears the input.
 *   - the same fetch() AJAX submit shape as event-schedule.js /
 *     client-information.js: clear errors -> require a payment option ->
 *     disable submit -> fetch() with Accept / Content-Type /
 *     X-CSRF-TOKEN -> on !response.ok read data.errors -> on success
 *     dispatch a bubbling CustomEvent.
 *   - Previous and Cancel both just dispatch bubbling CustomEvents
 *     (`booking:previous`, `booking:cancel`), the same technique
 *     event-schedule.js / client-information.js use — neither button
 *     lives inside <form> here, so nothing calls submit() directly.
 *
 * Request body: { payment_option: "full" } or
 * { payment_option: "down", down_payment_amount: <number> }. The server
 * must re-check down_payment_amount >= 30% of the STORED package cost
 * (this file's check is only a convenience).
 *
 * Expected back-end contract for `booking.payment`:
 *   200 JSON -> saved, caller advances to the Confirmation step
 *   422 {"message": "…", "errors": {"payment_option": ["…"]}} -> rejected
 *   422 {"message": "…", "errors": {"down_payment_amount": ["…"]}} -> amount rejected
 *   422 {"message": "…", "full_dates": ["YYYY-MM-DD", …]}
 *       -> a chosen day reached the event limit meanwhile;
 *
 * Proof of submission: a new payment also needs one image (input[data-proof-input], name
 * "payment_proof": JPG, PNG or WebP, up to 5 MB) showing the payment details form was
 * submitted. Chosen by click or drag and drop, previewed with a Remove button, checked in the
 * browser (convenience only; the server must check it again). Because a file is sent, the
 * request becomes multipart FormData (payment_option, down_payment_amount, payment_proof);
 * Content-Type is left for the browser to set. 422 {"errors": {"payment_proof": ["…"]}} shows
 * under the upload. Reschedules send no file and keep the JSON body.
 *
 * Reschedule mode (form[data-reschedule-id]): the
 * payment is carried over, so there is no payment option to pick. The body is
 * { reschedule_id } instead of { payment_option }; 403 {message} if the booking can't be
 * rescheduled. The "Confirm Reschedule" button sits in the actions row (form="payment-form").
 */

import { getCsrfToken } from "../auth/csrf.js";

const FORM_ID = "payment-form";
const MIN_DOWN_PAYMENT_RATE = 0.3;
const PROOF_TYPES = ["image/jpeg", "image/png", "image/webp"];
const PROOF_MAX_BYTES = 5 * 1024 * 1024;

function initBookingSummary() {
    initPaymentOptionToggle();
    initProofUpload();

    const form = document.getElementById(FORM_ID);
    if (!form) return;

    // Scoped to this step's own wrapper — every step's markup now
    // coexists in the DOM (toggled via d-none by booking-flow.js), so an
    // unscoped query would grab another step's Previous/Cancel button
    // instead of this one's.
    const step = form.closest(".booking-flow__view") || document;
    wireActionButtons(step);

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitPayment(form);
    });
}

function initPaymentOptionToggle() {
    const group = document.querySelector("[data-payment-option-group]");
    const hiddenInput = document.querySelector("[data-payment-option-input]");
    if (!group || !hiddenInput) return;

    const form = group.closest("form");
    const panel = form?.querySelector("[data-down-payment-panel]");
    const amountInput = form?.querySelector("[data-down-payment-input]");
    const buttons = group.querySelectorAll("[data-payment-option]");

    buttons.forEach((btn) => {
        btn.addEventListener("click", () => {
            buttons.forEach((other) =>
                other.setAttribute("aria-checked", "false"),
            );
            btn.setAttribute("aria-checked", "true");
            hiddenInput.value = btn.dataset.paymentOption;

            const errorEl = form?.querySelector(
                '[data-field-error="payment_option"]',
            );
            errorEl?.classList.add("d-none");

            const isDown = btn.dataset.paymentOption === "down";
            panel?.classList.toggle("d-none", !isDown);
            if (isDown) {
                updateDownPaymentHelp(form);
                amountInput?.focus();
            } else if (amountInput) {
                amountInput.value = "";
                clearAmountError(form);
                updatePercent(form);
            }
        });
    });

    if (amountInput) {
        // Live validation: re-check on every keystroke. An empty (or "."
        // only) field stays quiet while typing; the submit check still
        // asks for an amount.
        amountInput.addEventListener("input", () => {
            amountInput.value = sanitizeAmount(amountInput.value);
            updatePercent(form);
            if (amountInput.value === "" || amountInput.value === ".") {
                clearAmountError(form);
                return;
            }
            validateDownPayment(form);
        });
    }
}

// ---- Proof of submission upload ------------------------------------------------------------

function initProofUpload() {
    const wrap = document.querySelector("[data-proof-upload]");
    if (!wrap) return;

    const input = wrap.querySelector("[data-proof-input]");
    const dropzone = wrap.querySelector("[data-proof-dropzone]");
    const preview = wrap.querySelector("[data-proof-preview]");
    const image = wrap.querySelector("[data-proof-image]");
    const name = wrap.querySelector("[data-proof-name]");
    const remove = wrap.querySelector("[data-proof-remove]");
    const form = wrap.closest("form");
    let previewUrl = null;

    const reset = () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        input.value = "";
        image.removeAttribute("src");
        preview.classList.add("d-none");
        wrap.classList.remove("is-filled");
    };

    const accept = (file) => {
        clearProofError(form);
        const problem = proofProblem(file);
        if (problem) {
            reset();
            showProofError(form, problem);
            return;
        }
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        image.src = previewUrl;
        name.textContent = file.name;
        preview.classList.remove("d-none");
        wrap.classList.add("is-filled");
    };

    input.addEventListener("change", () => {
        if (input.files[0]) accept(input.files[0]);
        else reset();
    });

    remove.addEventListener("click", () => {
        reset();
        clearProofError(form);
        input.focus();
    });

    ["dragenter", "dragover"].forEach((type) =>
        dropzone.addEventListener(type, (event) => {
            event.preventDefault();
            dropzone.classList.add("is-dragover");
        }),
    );
    ["dragleave", "drop"].forEach((type) =>
        dropzone.addEventListener(type, () =>
            dropzone.classList.remove("is-dragover"),
        ),
    );
    dropzone.addEventListener("drop", (event) => {
        event.preventDefault();
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        accept(file);
    });
}

// Returns a message when the file can't be used, otherwise null.
function proofProblem(file) {
    if (!file)
        return "Please upload a screenshot showing you submitted the payment details form.";
    if (!PROOF_TYPES.includes(file.type))
        return "Please upload a JPG, PNG or WebP image.";
    if (file.size > PROOF_MAX_BYTES)
        return "The image is larger than 5 MB. Please choose a smaller one.";
    return null;
}

function showProofError(form, message) {
    const el = form?.querySelector('[data-field-error="payment_proof"]');
    if (el) {
        el.textContent = message;
        el.classList.remove("d-none");
    }
    form?.querySelector("[data-proof-upload]")?.classList.add("is-invalid");
}

function clearProofError(form) {
    const el = form?.querySelector('[data-field-error="payment_proof"]');
    if (el) {
        el.textContent = "";
        el.classList.add("d-none");
    }
    form?.querySelector("[data-proof-upload]")?.classList.remove("is-invalid");
}

// Numbers only: digits plus one decimal point, at most 2 decimals.
function sanitizeAmount(raw) {
    const cleaned = raw.replace(/[^\d.]/g, "");
    const [whole, ...rest] = cleaned.split(".");
    return rest.length ? `${whole}.${rest.join("").slice(0, 2)}` : whole;
}

const toCents = (value) => Math.round(Number(value) * 100);

const formatPhp = (cents) =>
    `Php ${(cents / 100).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;

function packageCostCents(form) {
    const cost = Number(form.dataset.packageCost);
    return Number.isFinite(cost) && cost > 0 ? toCents(cost) : 0;
}

function minDownPaymentCents(form) {
    return Math.ceil(packageCostCents(form) * MIN_DOWN_PAYMENT_RATE);
}

function updateDownPaymentHelp(form) {
    const help = form.querySelector("[data-down-payment-help]");
    const cost = packageCostCents(form);
    if (!help || !cost) return;
    help.textContent = `Package cost: ${formatPhp(cost)}. Enter at least 30% (${formatPhp(minDownPaymentCents(form))}). Numbers only.`;
}

// Live readout: what share of the package cost the typed amount is.
// Truncated (not rounded) to 1 decimal so 29.96% never reads as "30%".
function updatePercent(form) {
    const el = form.querySelector("[data-down-payment-percent]");
    const input = form.querySelector("[data-down-payment-input]");
    if (!el || !input) return;

    const cost = packageCostCents(form);
    const cents = toCents(input.value);
    if (!cost || !input.value || !Number.isFinite(cents) || cents <= 0) {
        el.textContent = "";
        el.classList.add("d-none");
        el.classList.remove("is-low", "is-ok");
        return;
    }

    const percent = Math.floor((cents / cost) * 1000) / 10;
    el.textContent = `${percent}% of your package cost`;
    el.classList.remove("d-none");
    el.classList.toggle("is-low", cents < minDownPaymentCents(form));
    el.classList.toggle("is-ok", cents >= minDownPaymentCents(form));
}

// Returns the amount in pesos when valid, otherwise shows the error and returns null.
function validateDownPayment(form) {
    const input = form.querySelector("[data-down-payment-input]");
    if (!input) return null;

    clearAmountError(form);

    const cents = toCents(input.value);
    if (!input.value || !Number.isFinite(cents) || cents <= 0) {
        showAmountError(form, "Please enter your down payment amount.");
        return null;
    }

    const cost = packageCostCents(form);
    const min = minDownPaymentCents(form);
    if (cost && cents < min) {
        showAmountError(
            form,
            `The down payment you entered (${formatPhp(cents)}) is lower than 30% of your selected package cost (${formatPhp(cost)}). The minimum is ${formatPhp(min)}.`,
        );
        return null;
    }

    return cents / 100;
}

function showAmountError(form, message) {
    const errorEl = form.querySelector(
        '[data-field-error="down_payment_amount"]',
    );
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
    form.querySelector(".booking-summary__amount")?.classList.add("is-invalid");
    form.querySelector("[data-down-payment-input]")?.setAttribute(
        "aria-invalid",
        "true",
    );
}

function clearAmountError(form) {
    const errorEl = form?.querySelector(
        '[data-field-error="down_payment_amount"]',
    );
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    form?.querySelector(".booking-summary__amount")?.classList.remove(
        "is-invalid",
    );
    form?.querySelector("[data-down-payment-input]")?.removeAttribute(
        "aria-invalid",
    );
}

function wireActionButtons(scope = document) {
    scope.querySelectorAll("[data-booking-previous]").forEach((btn) => {
        btn.addEventListener("click", () => {
            btn.dispatchEvent(
                new CustomEvent("booking:previous", { bubbles: true }),
            );
        });
    });

    scope.querySelectorAll("[data-booking-cancel]").forEach((btn) => {
        btn.addEventListener("click", () => {
            btn.dispatchEvent(
                new CustomEvent("booking:cancel", { bubbles: true }),
            );
        });
    });
}

async function submitPayment(form) {
    clearError(form);

    const hiddenInput = form.querySelector("[data-payment-option-input]");
    const rescheduleId = form.dataset.rescheduleId;
    if (!rescheduleId && !hiddenInput?.value) {
        showError(form, "Please select a payment option.");
        return;
    }

    let downPaymentAmount = null;
    if (!rescheduleId && hiddenInput.value === "down") {
        downPaymentAmount = validateDownPayment(form);
        if (downPaymentAmount === null) {
            form.querySelector("[data-down-payment-input]")?.focus();
            return;
        }
    }

    // New payments also need the proof image (a reschedule has no upload panel).
    const proofInput = form.querySelector("[data-proof-input]");
    clearProofError(form);
    if (!rescheduleId && proofInput) {
        const problem = proofProblem(proofInput.files[0]);
        if (problem) {
            showProofError(form, problem);
            form.querySelector("[data-proof-dropzone]")?.scrollIntoView({
                block: "center",
                behavior: "smooth",
            });
            return;
        }
    }

    const submitBtn = document.querySelector(
        '[form="payment-form"][type="submit"]',
    );
    if (submitBtn) submitBtn.disabled = true;

    try {
        // A file goes up, so a new payment is multipart FormData (no Content-Type: the browser
        // adds the boundary). A reschedule has no file and keeps its JSON body.
        const headers = {
            Accept: "application/json",
            "X-CSRF-TOKEN": getCsrfToken(form),
        };
        let body;
        if (rescheduleId) {
            headers["Content-Type"] = "application/json";
            body = JSON.stringify({ reschedule_id: rescheduleId });
        } else {
            body = new FormData();
            body.append("payment_option", hiddenInput.value);
            if (downPaymentAmount !== null) {
                body.append("down_payment_amount", downPaymentAmount);
            }
            body.append("payment_proof", proofInput.files[0]);
        }

        const response = await fetch(form.action, {
            method: "POST",
            headers,
            body,
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            // Capacity race: a chosen day filled up after Event Schedule saved. Hand it to the flow.
            if (Array.isArray(data.full_dates) && data.full_dates.length) {
                form.dispatchEvent(
                    new CustomEvent("booking:schedule-conflict", {
                        bubbles: true,
                        detail: {
                            fullDates: data.full_dates,
                            message: data.message,
                        },
                    }),
                );
                return;
            }

            const amountError = data.errors?.down_payment_amount?.[0];
            if (amountError) {
                showAmountError(form, amountError);
                return;
            }

            const proofError = data.errors?.payment_proof?.[0];
            if (proofError) {
                showProofError(form, proofError);
                return;
            }

            const message =
                data.errors?.payment_option?.[0] ??
                data.message ??
                "Something went wrong. Please try again.";
            showError(form, message);
            return;
        }

        form.dispatchEvent(
            new CustomEvent("booking:payment-saved", {
                bubbles: true,
                detail: { reference: data.reference },
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

function showError(form, message) {
    const errorEl = form.querySelector("[data-payment-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
}

function clearError(form) {
    const errorEl = form.querySelector("[data-payment-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
}

document.addEventListener("DOMContentLoaded", initBookingSummary);

export { initBookingSummary };
