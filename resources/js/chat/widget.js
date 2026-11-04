// User-side floating chat: open/close, focus handling, the shared composer and the
// live unread/reply stream.
import {
    initChat,
    initChatStream,
    scrollLog,
    showUnreadDivider,
} from "./chat.js";

const root = document.querySelector("[data-chat-widget]");

if (root) {
    const fab = root.querySelector("[data-chat-toggle]");
    const panel = root.querySelector("[data-chat-panel]");
    const log = root.querySelector("[data-chat-log]");
    let returnFocusTarget = fab;

    // Phones: the panel is full-screen. When the on-screen keyboard opens, follow the visible part
    // of the screen so the composer is never hidden behind it.
    const phone = window.matchMedia("(max-width: 575.98px)");
    const fitToScreen = () => {
        const vv = window.visualViewport;
        if (vv && phone.matches && !panel.hidden) {
            // `important`: the panel's utilities are `!important`, a plain inline value would lose.
            panel.style.setProperty("height", `${vv.height}px`, "important");
            panel.style.setProperty("top", `${vv.offsetTop}px`, "important");
        } else {
            panel.style.removeProperty("height");
            panel.style.removeProperty("top");
        }
    };
    window.visualViewport?.addEventListener("resize", fitToScreen);
    window.visualViewport?.addEventListener("scroll", fitToScreen);
    phone.addEventListener("change", fitToScreen);

    const setOpen = (open, trigger = returnFocusTarget) => {
        if (open) returnFocusTarget = trigger;
        panel.hidden = !open;
        // While the full-screen chat is open on a phone, the page behind it must not scroll.
        document.documentElement.classList.toggle(
            "max-[575.98px]:overflow-hidden",
            open,
        );
        fitToScreen();
        root.querySelectorAll("[data-chat-toggle], [data-chat-open]").forEach(
            (button) => {
                button.setAttribute("aria-expanded", String(open));
            },
        );
        if (open) {
            // Read the unread count before the thread is marked read (that clears the badge).
            const unread =
                Number.parseInt(
                    root.querySelector("[data-chat-badge]")?.textContent,
                    10,
                ) || 0;
            // The backend marks this client's thread read; the badge clears and stays clear.
            root.dispatchEvent(
                new CustomEvent("chat:opened", { bubbles: true }),
            );
            // Land on the first unread message (with a "New messages" divider), else the newest.
            if (log && !showUnreadDivider(log, unread)) scrollLog(log);
            (
                root.querySelector("[data-chat-input]") ||
                root.querySelector("[data-chat-close]")
            )?.focus();
        } else {
            root.dispatchEvent(
                new CustomEvent("chat:closed", { bubbles: true }),
            );
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
    initChatStream(root);
}
