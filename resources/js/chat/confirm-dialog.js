/**
 * Confirmation modal shared by chat (Unsend) and Log out (user account and admin).
 * A real <dialog>: focus stays inside, Esc / the X / Cancel / a click on the dimmed area cancel it.
 * Styles: utility classes (the `CM` table below); `.chat-modal*` stay as hooks.
 *
 *   const ok = await confirmDialog({ title, text, confirmLabel });   // true only on confirm
 *
 * Browsers without <dialog> fall back to window.confirm().
 */

// Utility strings, whole, so Tailwind can find them. The BEM names stay as hooks.
const CM = {
    dialog: "chat-modal m-auto max-h-[calc(100dvh_-_2rem)] w-[min(32rem,calc(100vw_-_2rem))] overflow-auto rounded-panel border-0 bg-surface p-0 font-[family-name:inherit] text-ink shadow-[0_1.5rem_4rem_oklch(0%_0_0/0.35)] backdrop:bg-shadow",
    head: "chat-modal__head relative flex items-center justify-center border-b border-panel px-14 py-4",
    title: "chat-modal__title m-0 text-center text-[1.25rem] leading-[1.3] font-bold",
    close: "chat-modal__close absolute top-1/2 right-4 grid size-9 -translate-y-1/2 cursor-pointer place-items-center rounded-full border-0 bg-panel p-0 text-[1.25rem] text-ink hover:bg-chat-tint focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text",
    text: "chat-modal__text m-0 px-6 py-5 text-[1rem] leading-normal text-ink-muted",
    foot: "chat-modal__foot flex flex-wrap justify-end gap-2 px-6 pt-2 pb-5",
    cancel: "chat-modal__cancel min-h-10 cursor-pointer rounded-full border-0 bg-transparent px-5 py-2 font-semibold text-brand-text hover:bg-brand-halo focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text",
    confirm:
        "chat-modal__confirm min-h-10 cursor-pointer rounded-full border-0 bg-brand px-5 py-2 font-semibold text-surface hover:bg-brand-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text",
};

let seq = 0;

const el = (tag, cls, text) => {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
};

// Constant markup only, never user text.
const closeIcon = () => {
    const t = document.createElement("template");
    t.innerHTML =
        '<svg class="chat-icon block size-[1em] flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
    return t.content.firstChild;
};

export function confirmDialog({ title, text, confirmLabel = "Confirm" }) {
    if (typeof HTMLDialogElement === "undefined") {
        return Promise.resolve(window.confirm(title));
    }
    return new Promise((resolve) => {
        const id = `confirmDialog${++seq}`;
        const dialog = el("dialog", CM.dialog);
        dialog.setAttribute("aria-labelledby", `${id}Title`);
        dialog.setAttribute("aria-describedby", `${id}Text`);

        const head = el("div", CM.head);
        const heading = el("h2", CM.title, title);
        heading.id = `${id}Title`;
        const close = el("button", CM.close);
        close.type = "button";
        close.setAttribute("aria-label", "Close");
        close.append(closeIcon());
        head.append(heading, close);

        const body = el("p", CM.text, text);
        body.id = `${id}Text`;

        const foot = el("div", CM.foot);
        const cancel = el("button", CM.cancel, "Cancel");
        cancel.type = "button";
        cancel.autofocus = true; // the safe choice has focus
        const confirm = el("button", CM.confirm, confirmLabel);
        confirm.type = "button";
        foot.append(cancel, confirm);

        dialog.append(head, body, foot);

        let result = false;
        confirm.addEventListener("click", () => {
            result = true;
            dialog.close();
        });
        cancel.addEventListener("click", () => dialog.close());
        close.addEventListener("click", () => dialog.close());
        // A click on the dimmed area (the <dialog> itself) cancels; clicks never reach the page
        // behind, so the chat widget does not treat them as "click outside".
        dialog.addEventListener("click", (e) => {
            e.stopPropagation();
            if (e.target === dialog) dialog.close();
        });
        // Esc closes the dialog natively; keep it from also closing the chat widget.
        dialog.addEventListener("keydown", (e) => {
            if (e.key === "Escape") e.stopPropagation();
        });
        dialog.addEventListener("close", () => {
            dialog.remove();
            resolve(result);
        });

        document.body.append(dialog);
        dialog.showModal();
    });
}
