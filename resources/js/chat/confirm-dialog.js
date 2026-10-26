/**
 * Confirmation modal shared by chat (Unsend) and Log out (user account and admin).
 * A real <dialog>: focus stays inside, Esc / the X / Cancel / a click on the dimmed area cancel it.
 * Styles: `.chat-modal` in sass/chat/_chat.scss (compiled into admin.scss and account.scss).
 *
 *   const ok = await confirmDialog({ title, text, confirmLabel });   // true only on confirm
 *
 * Browsers without <dialog> fall back to window.confirm().
 */

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
        const dialog = el("dialog", "chat-modal");
        dialog.setAttribute("aria-labelledby", `${id}Title`);
        dialog.setAttribute("aria-describedby", `${id}Text`);

        const head = el("div", "chat-modal__head");
        const heading = el("h2", "chat-modal__title", title);
        heading.id = `${id}Title`;
        const close = el("button", "chat-modal__close");
        close.type = "button";
        close.setAttribute("aria-label", "Close");
        close.append(closeIcon());
        head.append(heading, close);

        const body = el("p", "chat-modal__text", text);
        body.id = `${id}Text`;

        const foot = el("div", "chat-modal__foot");
        const cancel = el("button", "chat-modal__cancel", "Cancel");
        cancel.type = "button";
        cancel.autofocus = true; // the safe choice has focus
        const confirm = el("button", "chat-modal__confirm", confirmLabel);
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
