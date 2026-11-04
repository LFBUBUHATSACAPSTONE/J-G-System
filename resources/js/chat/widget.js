// User-side floating chat: open/close, focus handling, and the shared composer.
import { initChat, scrollLog } from "./chat.js";

const root = document.querySelector("[data-chat-widget]");

if (root) {
    const fab = root.querySelector("[data-chat-toggle]");
    const panel = root.querySelector("[data-chat-panel]");
    const log = root.querySelector("[data-chat-log]");
    const dot = root.querySelector("[data-chat-dot]");

    const setOpen = (open) => {
        panel.hidden = !open;
        fab.setAttribute("aria-expanded", String(open));
        if (open) {
            dot?.remove(); // unread is cleared by the backend later; here it just hides
            if (log) scrollLog(log);
            (
                root.querySelector("[data-chat-input]") ||
                root.querySelector("[data-chat-close]")
            )?.focus();
        } else {
            fab.focus();
        }
    };

    fab.addEventListener("click", () => setOpen(panel.hidden));
    root.querySelector("[data-chat-close]").addEventListener("click", () =>
        setOpen(false),
    );
    panel.addEventListener("keydown", (e) => {
        if (e.key === "Escape") setOpen(false);
    });

    initChat(root);
}
