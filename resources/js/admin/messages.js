// Admin Messages page: chat list (select / search / unread filter), panels, quick replies,
// mobile list <-> conversation switch. Everything is client-side over the chats on the page.
import {
    appendBooking,
    appendIncoming,
    applyMessageChange,
    formatListStamp,
    formatStamp,
    initChat,
    scrollLog,
    showUnreadDivider,
} from "../chat/chat.js";

const layout = document.querySelector("[data-messages]");

if (layout) {
    const list = layout.querySelector("[data-chat-list]");
    const items = [...layout.querySelectorAll("[data-chat-item]")];
    const search = layout.querySelector("[data-chat-search]");
    const filters = [...layout.querySelectorAll("[data-chat-filter]")];
    const count = layout.querySelector("[data-chat-count]");
    const panels = [...layout.querySelectorAll(".chat-panel")];
    let filter = "all";

    // The list stamps are server-rendered in UTC (same as the thread); show them in the admin's
    // own timezone, short ("7:45 PM", "Yesterday", "Oct 15"), with the full stamp on hover.
    layout.querySelectorAll("[data-chat-when][data-at]").forEach((when) => {
        const at = new Date(when.dataset.at);
        if (Number.isNaN(at.getTime())) return;
        when.textContent = formatListStamp(at);
        when.title = formatStamp(at);
    });

    // Stamp a list row with the current time, keeping data-at in sync so a later
    // re-localization would agree with it.
    const touchWhen = (btn) => {
        const when = btn.querySelector("[data-chat-when]");
        if (!when) return;
        const now = new Date();
        when.textContent = formatListStamp(now);
        when.title = formatStamp(now);
        when.dataset.at = now.toISOString();
    };

    // How many of this chat's messages are still unread, from its list row.
    const unreadOf = (id) =>
        +items.find((li) => li.querySelector(`[data-chat-open="${id}"]`))
            ?.dataset.unread || 0;

    // Land on the first unread message (with a "New messages" divider), else the newest.
    const settle = (panel, unread) => {
        const log = panel?.querySelector("[data-chat-log]");
        if (log && !showUnreadDivider(log, unread)) scrollLog(log);
    };

    panels.forEach((p) => initChat(p));
    const first = panels.find((p) => !p.hidden);
    if (first) settle(first, unreadOf(first.id));

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
        const unread = unreadOf(id); // before the row's unread flag is cleared below
        panels.forEach((p) => {
            p.hidden = p.id !== id;
        });
        layout.querySelectorAll("[data-chat-open]").forEach((b) => {
            const on = b.dataset.chatOpen === id;
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
        // Show the conversation first (on phones the panels are hidden while the list is up), then
        // position the thread: a hidden thread has no size to scroll.
        layout.dataset.view = "chat";
        settle(panel, unread);
        markPanelRead(panel);
        applyFilters();
    }

    function applyFilters() {
        const q = (search.value || "").trim().toLowerCase();
        let shown = 0;
        items.forEach((li) => {
            const active = li.querySelector("[aria-current]");
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
                f.setAttribute("aria-pressed", String(f === b));
            });
            applyFilters();
        }),
    );

    // After a send: update that chat's preview and time, and move it to the top.
    layout.addEventListener("chat:sent", (e) => {
        const btn = layout.querySelector(`[data-chat-open="${e.target.id}"]`);
        if (!btn) return;
        const { body, files } = e.detail;
        btn.querySelector("[data-chat-preview]").textContent =
            `You: ${body || (files > 1 ? "Sent attachments" : "Sent an attachment")}`;
        touchWhen(btn);
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
                (max, node) =>
                    Math.max(max, Number(node.dataset.messageId) || 0),
                0,
            );

        const setSidebarBadge = (n) => {
            document
                .querySelectorAll("[data-chat-unread-badge]")
                .forEach((b) => {
                    b.textContent = n > 9 ? "9+" : String(n);
                    b.hidden = !n;
                });
        };

        // Pace (same as chat.js initChatStream): 3 s while the window has focus, 15 s in the
        // background, paused while the tab is hidden, backing off (up to 60 s) after errors.
        let busy = false;
        let timer = 0;
        let failures = 0;
        const schedule = () => {
            window.clearTimeout(timer);
            if (document.hidden) return; // resumes on visibilitychange
            const base = document.hasFocus() ? 3000 : 15000;
            timer = window.setTimeout(
                poll,
                Math.min(base * 2 ** failures, 60000),
            );
        };
        const poll = () => {
            if (busy || document.hidden) return;
            busy = true;
            window.clearTimeout(timer);
            fetch(
                `${updatesUrl}?after=${maxId()}&since=${encodeURIComponent(since)}&changed_since=${encodeURIComponent(changedSince)}`,
                { headers: { Accept: "application/json" } },
            )
                .then((res) => {
                    if (!res.ok) throw new Error(String(res.status));
                    return res.json();
                })
                .then((data) => {
                    failures = 0;
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
                        if (!log || !appendIncoming(log, message, panel))
                            return;

                        // Refresh the chat-list row: preview, time, then move it up.
                        const li = btn.closest("[data-chat-item]");
                        const text =
                            message.body ||
                            (message.attachments?.length
                                ? "Sent an attachment"
                                : "");
                        const nameEl = btn.querySelector(".chat-item__name");
                        const preview = btn.querySelector(
                            "[data-chat-preview]",
                        );
                        if (preview)
                            preview.textContent = `${nameEl ? nameEl.textContent : "Client"}: ${text}`;
                        touchWhen(btn);
                        if (li) list.prepend(li);

                        if (panel.hidden) {
                            // Not the open chat: flag it unread.
                            if (li) {
                                li.dataset.unread = String(
                                    (+li.dataset.unread || 0) + 1,
                                );
                                const badge =
                                    btn.querySelector("[data-chat-badge]");
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
                    if (data.bookings_synced_at)
                        since = data.bookings_synced_at;
                    if (data.messages_synced_at)
                        changedSince = data.messages_synced_at;

                    setSidebarBadge(data.unread || 0);

                    // New conversation: reload once so its row and full thread render
                    // in, but never clobber a draft the admin is typing.
                    if (sawNewConversation) {
                        const typing = [
                            ...layout.querySelectorAll("[data-chat-input]"),
                        ].some((input) => input.value.trim() !== "");
                        if (!typing) {
                            window.location.reload();
                            return;
                        }
                    }

                    applyFilters();
                })
                .catch(() => {
                    failures = Math.min(failures + 1, 4);
                })
                .finally(() => {
                    busy = false;
                    schedule();
                });
        };

        const wake = () => {
            if (document.hidden) {
                window.clearTimeout(timer);
                return;
            }
            poll();
            schedule();
        };
        document.addEventListener("visibilitychange", wake);
        window.addEventListener("focus", wake);
        window.addEventListener("blur", schedule);

        poll();
        schedule();
    }
}
