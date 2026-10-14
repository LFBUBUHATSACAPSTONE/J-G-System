// Admin Messages page: chat list (select / search / unread filter), panels, quick replies,
// mobile list <-> conversation switch. Everything is client-side over the chats on the page.
import { appendBooking, appendIncoming, applyMessageChange, initChat, scrollLog } from "../chat/chat.js";

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

    // Opening a chat clears its "unread" flag for real: the GET marks the client
    // messages read in the database, so the badge is gone after a reload too.
    const readDone = new Set();
    function markPanelRead(panel) {
        const url = panel?.dataset.chatReadUrl;
        if (!url || readDone.has(panel.id)) return;
        readDone.add(panel.id);
        fetch(url, { headers: { Accept: "application/json" } }).catch(() =>
            readDone.delete(panel.id),
        );
    }

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
        markPanelRead(panel);
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

    // ---- live updates: new client messages arrive without a refresh ----------
    // Mirrors the user side (chat.js `initChatStream`): poll `admin.messages.updates`,
    // drop each new client message into its conversation, and refresh the list
    // preview + unread badges. `after` is the highest message id already on the page.
    const updatesUrl = layout.dataset.chatUpdatesUrl || "";
    if (updatesUrl) {
        // Cursor for the booking feed: bookings changed after this moment pop into
        // their client's panel. Starts at page render, advances to the server clock.
        let since = layout.dataset.chatBookingsSince || "";
        // Cursor for the edit/unsend feed: messages changed after this moment are re-applied.
        let changedSince = layout.dataset.chatMessagesSince || "";
        const maxId = () =>
            [...layout.querySelectorAll("[data-message-id]")].reduce(
                (max, node) => Math.max(max, Number(node.dataset.messageId) || 0),
                0,
            );

        const setSidebarBadge = (n) => {
            document.querySelectorAll("[data-chat-unread-badge]").forEach((b) => {
                b.textContent = n > 9 ? "9+" : String(n);
                b.hidden = !n;
            });
        };

        let busy = false;
        const poll = () => {
            if (busy || document.hidden) return;
            busy = true;
            fetch(
                `${updatesUrl}?after=${maxId()}&since=${encodeURIComponent(since)}&changed_since=${encodeURIComponent(changedSince)}`,
                { headers: { Accept: "application/json" } },
            )
                .then((res) => (res.ok ? res.json() : null))
                .then((data) => {
                    if (!data) return;
                    let sawNewConversation = false;
                    (data.messages || []).forEach((message) => {
                        const panel = document.getElementById(
                            `chat-${message.conversation_id}`,
                        );
                        const btn = layout.querySelector(
                            `[data-chat-open="chat-${message.conversation_id}"]`,
                        );
                        if (!panel || !btn) {
                            // A client's very first message: the conversation did not
                            // exist when the page rendered, so there is no row/panel yet.
                            sawNewConversation = true;
                            return;
                        }
                        const log = panel.querySelector("[data-chat-log]");
                        if (!log || !appendIncoming(log, message, panel)) return;

                        // Refresh the chat-list row: preview, time, then move it up.
                        const li = btn.closest("[data-chat-item]");
                        const text =
                            message.body ||
                            (message.attachments?.length ? "Sent an attachment" : "");
                        const nameEl = btn.querySelector(".chat-item__name");
                        const preview = btn.querySelector("[data-chat-preview]");
                        if (preview)
                            preview.textContent = `${nameEl ? nameEl.textContent : "Client"}: ${text}`;
                        const when = btn.querySelector("[data-chat-when]");
                        if (when) when.textContent = "now";
                        if (li) list.prepend(li);

                        if (panel.hidden) {
                            // Not the open chat: flag it unread.
                            if (li) {
                                li.dataset.unread = String((+li.dataset.unread || 0) + 1);
                                const badge = btn.querySelector("[data-chat-badge]");
                                if (badge) {
                                    badge.textContent = li.dataset.unread;
                                    badge.hidden = false;
                                }
                            }
                        } else {
                            // Open chat: show it and clear it right away.
                            readDone.delete(panel.id);
                            markPanelRead(panel);
                        }
                    });

                    // Edits/unsends: update or drop the message wherever it is on the page.
                    (data.messages_changed || []).forEach((message) => {
                        const panel = document.getElementById(
                            `chat-${message.conversation_id}`,
                        );
                        const log = panel?.querySelector("[data-chat-log]");
                        if (log) applyMessageChange(log, message);
                    });

                    // Bookings: drop each new card into its client's panel.
                    (data.bookings || []).forEach((booking) => {
                        const panel = document.getElementById(
                            `chat-${booking.conversation_id}`,
                        );
                        const log = panel?.querySelector("[data-chat-log]");
                        if (log) appendBooking(log, panel, booking);
                    });
                    if (data.bookings_synced_at) since = data.bookings_synced_at;
                    if (data.messages_synced_at)
                        changedSince = data.messages_synced_at;

                    setSidebarBadge(data.unread || 0);

                    // New conversation: reload once so its row and full thread render
                    // in, but never clobber a draft the admin is typing.
                    if (sawNewConversation) {
                        const typing = [...layout.querySelectorAll("[data-chat-input]")].some(
                            (input) => input.value.trim() !== "",
                        );
                        if (!typing) {
                            window.location.reload();
                            return;
                        }
                    }

                    applyFilters();
                })
                .catch(() => {})
                .finally(() => {
                    busy = false;
                });
        };

        setInterval(poll, 5000);
        poll();
    }
}
