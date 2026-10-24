/**
 * Shared confirmation modal for booking actions (views/components/booking/action-modal.blade.php).
 * Used by the admin Bookings page (Approve, Decline, Verify Payment, Cancel) and by the client's
 * My Bookings (Cancel booking). FRONT END ONLY: this file never decides anything, it asks the
 * person, hands the answer to `onConfirm`, and shows the server's message if that fails.
 *
 *   openActionModal(modalElement, {
 *     title, message,                 text shown in the modal
 *     confirmLabel, tone,             confirm button text; tone = danger | success | brand
 *     reason: true,                   ask for the required reason (modal rendered with :reason="true")
 *     reasonLabel, reasonHint,        heading and help text of that box
 *     onConfirm: async (reason) => {} do the request here; throw an Error(message) to keep the
 *                                     modal open and show that message
 *   }) -> Promise<boolean>            true once onConfirm finished, false if the person closed it
 *
 * The reason is trimmed and checked against data-min / data-max (config/cancellation.php) before
 * onConfirm runs; a 422 from the server for the same rule shows up under the box.
 */

import { Modal } from "bootstrap";

const states = new WeakMap();
const TONES = ["danger", "success", "brand"];

function setup(el) {
    if (states.has(el)) return states.get(el);

    const q = (selector) => el.querySelector(selector);
    const state = {
        title: q("[data-action-title]"),
        message: q("[data-action-message]"),
        confirm: q("[data-action-confirm]"),
        back: q("[data-action-back]"),
        error: q("[data-action-error]"),
        field: q("[data-action-reason-field]"),
        reason: q("[data-action-reason]"),
        reasonLabel: q("[data-action-reason-label]"),
        reasonHint: q("[data-action-reason-hint]"),
        reasonError: q("[data-action-reason-error]"),
        count: q("[data-action-count]"),
        min: Number(el.dataset.min) || 0,
        max: Number(el.dataset.max) || 0,
        opts: null,
        busy: false,
        done: false,
        touched: false,
        resolve: null,
        trigger: null,
    };
    states.set(el, state);

    const show = (target, message) => {
        target.textContent = message || "";
        target.classList.toggle("d-none", !message);
    };

    const reasonError = () => {
        if (!state.reason || !state.opts?.reason) return "";
        const length = state.reason.value.trim().length;
        if (length === 0) return "Please tell us the reason.";
        if (length < state.min)
            return `Please write at least ${state.min} characters.`;
        if (state.max && length > state.max)
            return `Please keep it to ${state.max} characters or fewer.`;
        return "";
    };

    const paintReason = () => {
        if (!state.reason) return;
        state.count.textContent = `${state.reason.value.length}/${state.max}`;
        const message = state.touched ? reasonError() : "";
        show(state.reasonError, message);
        state.reason.classList.toggle("is-invalid", Boolean(message));
        state.reason.setAttribute("aria-invalid", message ? "true" : "false");
    };

    state.reason?.addEventListener("input", () => {
        show(state.error, "");
        paintReason();
    });
    state.reason?.addEventListener("blur", () => {
        state.touched = true;
        paintReason();
    });

    const setBusy = (busy) => {
        state.busy = busy;
        state.confirm.disabled = busy;
        state.back.disabled = busy;
        state.confirm.classList.toggle("is-busy", busy);
    };

    state.confirm.addEventListener("click", async () => {
        if (state.busy || !state.opts) return;
        show(state.error, "");

        if (state.opts.reason && state.reason) {
            state.touched = true;
            paintReason();
            if (reasonError()) {
                state.reason.focus();
                return;
            }
        }

        setBusy(true);
        try {
            await state.opts.onConfirm?.(
                state.opts.reason ? state.reason.value.trim() : null,
            );
            state.done = true;
            Modal.getInstance(el)?.hide();
        } catch (error) {
            // A reason the server refused belongs under the box, anything else above the buttons.
            const message =
                error instanceof Error && error.message
                    ? error.message
                    : "Something went wrong. Please try again.";
            if (error?.field === "reason" && state.reasonError) {
                state.touched = true;
                state.reasonError.textContent = message;
                state.reasonError.classList.remove("d-none");
                state.reason.classList.add("is-invalid");
                state.reason.focus();
            } else {
                show(state.error, message);
            }
        } finally {
            setBusy(false);
        }
    });

    // Keep it open while a request is running: Esc and the backdrop would hide it mid-request.
    el.addEventListener("hide.bs.modal", (event) => {
        if (state.busy) event.preventDefault();
    });
    el.addEventListener("shown.bs.modal", () => {
        (state.opts?.reason && state.reason
            ? state.reason
            : state.confirm
        ).focus();
    });
    el.addEventListener("hidden.bs.modal", () => {
        const resolve = state.resolve;
        state.resolve = null;
        resolve?.(state.done);
        // Hand focus back to the button that opened it.
        if (state.trigger?.isConnected) state.trigger.focus();
        state.trigger = null;
    });

    state.show = show;
    state.paintReason = paintReason;
    return state;
}

export function openActionModal(el, opts) {
    const state = setup(el);
    state.opts = opts;
    state.done = false;
    state.touched = false;
    state.trigger = document.activeElement;

    state.title.textContent = opts.title ?? "Are you sure?";
    state.message.textContent = opts.message ?? "";
    state.message.classList.toggle("d-none", !opts.message);
    state.confirm.textContent = opts.confirmLabel ?? "Confirm";
    TONES.forEach((tone) =>
        state.confirm.classList.toggle(
            `action-modal__btn--${tone}`,
            tone === (opts.tone ?? "brand"),
        ),
    );
    state.show(state.error, "");

    state.field?.classList.toggle("d-none", !opts.reason);
    if (state.reason) {
        state.reason.value = "";
        if (opts.reasonLabel) state.reasonLabel.textContent = opts.reasonLabel;
        if (opts.reasonHint) state.reasonHint.textContent = opts.reasonHint;
        state.paintReason();
    }

    return new Promise((resolve) => {
        state.resolve = resolve;
        Modal.getOrCreateInstance(el).show();
    });
}

/** Error to throw from onConfirm when the server refused the reason itself (422 on `reason`). */
export function reasonError(message) {
    const error = new Error(message);
    error.field = "reason";
    return error;
}
