// Admin Messages page: chat list (select / search / unread filter), panels, quick replies,
// mobile list <-> conversation switch. Everything is client-side over the chats on the page.
import { initChat, scrollLog } from "../chat/chat.js";

const layout = document.querySelector("[data-messages]");

if (layout) {
    const list = layout.querySelector("[data-chat-list]");
    const items = [...layout.querySelectorAll("[data-chat-item]")];
    const search = layout.querySelector("[data-chat-search]");
    const filters = [...layout.querySelectorAll("[data-chat-filter]")];
    const count = layout.querySelector("[data-chat-count]");
    const panels = [...layout.querySelectorAll(".chat-panel")];
    let filter = "all";

    panels.forEach((p) => initChat(p));
    const first = panels.find((p) => !p.hidden);
    if (first) scrollLog(first.querySelector("[data-chat-log]"));

    function open(id) {
        panels.forEach((p) => {
            p.hidden = p.id !== id;
        });
        layout.querySelectorAll("[data-chat-open]").forEach((b) => {
            const on = b.dataset.chatOpen === id;
            b.classList.toggle("is-active", on);
            on
                ? b.setAttribute("aria-current", "true")
                : b.removeAttribute("aria-current");
            if (on) {
                const li = b.closest("[data-chat-item]");
                li.dataset.unread = "0";
                b.querySelector("[data-chat-badge]").hidden = true;
            }
        });
        const panel = document.getElementById(id);
        scrollLog(panel.querySelector("[data-chat-log]"));
        layout.dataset.view = "chat";
        applyFilters();
    }

    function applyFilters() {
        const q = (search.value || "").trim().toLowerCase();
        let shown = 0;
        items.forEach((li) => {
            const active = li.querySelector(".is-active");
            const ok =
                (!q || li.dataset.name.includes(q)) &&
                (filter === "all" || +li.dataset.unread > 0 || active);
            li.hidden = !ok;
            if (ok) shown += 1;
        });
        if (count)
            count.textContent = `Showing ${shown} of ${items.length} chats`;
    }

    list?.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-chat-open]");
        if (btn) open(btn.dataset.chatOpen);
    });

    layout.querySelectorAll("[data-chat-back]").forEach((b) =>
        b.addEventListener("click", () => {
            layout.dataset.view = "list";
        }),
    );

    search?.addEventListener("input", applyFilters);
    filters.forEach((b) =>
        b.addEventListener("click", () => {
            filter = b.dataset.chatFilter;
            filters.forEach((f) => {
                f.classList.toggle("is-active", f === b);
                f.setAttribute("aria-pressed", String(f === b));
            });
            applyFilters();
        }),
    );

    // Quick replies fill the box; the admin reviews and presses Send.
    layout.addEventListener("click", (e) => {
        const chip = e.target.closest("[data-chat-quick]");
        if (!chip) return;
        const input = chip
            .closest("[data-chat]")
            .querySelector("[data-chat-input]");
        input.value = chip.dataset.chatQuick;
        input.dispatchEvent(new Event("input"));
        input.focus();
    });

    // After a send: update that chat's preview and time, and move it to the top.
    layout.addEventListener("chat:sent", (e) => {
        const btn = layout.querySelector(`[data-chat-open="${e.target.id}"]`);
        if (!btn) return;
        const { body, files } = e.detail;
        btn.querySelector("[data-chat-preview]").textContent =
            `You: ${body || (files > 1 ? "Sent attachments" : "Sent an attachment")}`;
        btn.querySelector("[data-chat-when]").textContent = "now";
        list.prepend(btn.closest("[data-chat-item]"));
    });

    applyFilters();
}
