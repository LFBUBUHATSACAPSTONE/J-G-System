// User-side floating chat: open/close, focus handling, and the shared composer.
import { initChat, scrollLog } from "./chat.js";

const root = document.querySelector("[data-chat-widget]");

if (root) {
    const fab = root.querySelector("[data-chat-toggle]");
    const panel = root.querySelector("[data-chat-panel]");
    const log = root.querySelector("[data-chat-log]");
    const dot = root.querySelector("[data-chat-dot]");
    let returnFocusTarget = fab;

    const setOpen = (open, trigger = returnFocusTarget) => {
        if (open) returnFocusTarget = trigger;
        panel.hidden = !open;
        root.querySelectorAll("[data-chat-toggle], [data-chat-open]").forEach((button) => {
            button.setAttribute("aria-expanded", String(open));
        });
        if (open) {
            dot?.remove(); // unread is cleared by the backend later; here it just hides
            if (log) scrollLog(log);
            (
                root.querySelector("[data-chat-input]") ||
                root.querySelector("[data-chat-close]")
            )?.focus();
        } else {
            returnFocusTarget.focus();
        }
    };

    fab.addEventListener("click", () => setOpen(panel.hidden, fab));
    document.querySelectorAll("[data-chat-open]").forEach((button) => {
        button.addEventListener("click", () => setOpen(panel.hidden, button));
    });
    root.querySelector("[data-chat-close]").addEventListener("click", () =>
        setOpen(false),
    );
    panel.addEventListener("keydown", (e) => {
        if (e.key === "Escape") setOpen(false);
    });

    initChat(root);
}
