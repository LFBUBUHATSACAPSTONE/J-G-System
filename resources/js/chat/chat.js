// Shared chat behaviour (admin panel + user widget): composer, attachments, optimistic send,
// reply, edit and unsend. Front end only: POSTs to the form's action (a named route) and expects
// 200 {ok:true} or 422 {message, errors}. Same fetch header pattern as the auth and booking forms.
import { getCsrfToken } from "../auth/csrf.js";

const el = (tag, cls, text) => {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
};
// Inline SVG (the user pages load no icon font). Constant markup only, never user text.
const ICONS = {
    x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    file: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>',
};
const icon = (name) => {
    const t = document.createElement("template");
    t.innerHTML = `<svg class="chat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${ICONS[name]}</svg>`;
    return t.content.firstChild;
};
const fmtSize = (b) =>
    b < 1048576
        ? `${Math.max(1, Math.round(b / 1024))} KB`
        : `${(b / 1048576).toFixed(1)} MB`;
const formatTime = (date) =>
    date.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" });
const dayKeyOf = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
const startOfDay = (date) =>
    new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
const dayLabel = (date) => {
    const daysAgo = Math.round(
        (startOfDay(new Date()) - startOfDay(date)) / 86400000,
    );
    if (daysAgo === 0) return "Today";
    if (daysAgo === 1) return "Yesterday";
    return date.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
};
const timeLabel = () => formatTime(new Date());
const dayKey = () => dayKeyOf(new Date());

// `data-chat-update-url` / `data-chat-delete-url` hold a route with the token below in
// place of the message id, so one template covers every message.
const messageUrl = (template, id) =>
    template ? template.replace("__MESSAGE__", encodeURIComponent(id)) : "";

export const scrollLog = (log) => {
    log.scrollTop = log.scrollHeight;
};

// The server renders timestamps in UTC; rewrite them in the visitor's own timezone so a
// refreshed thread agrees with the times shown for freshly sent and polled messages.
export function localizeTimes(log) {
    if (!log) return;
    log.querySelectorAll(".chat-day[data-at]").forEach((day) => {
        const at = new Date(day.dataset.at);
        if (Number.isNaN(at.getTime())) return;
        day.textContent = dayLabel(at);
        day.dataset.day = dayKeyOf(at);
    });
    log.querySelectorAll("time.chat-msg__time[datetime]").forEach((time) => {
        const at = new Date(time.getAttribute("datetime"));
        if (Number.isNaN(at.getTime())) return;
        time.textContent = formatTime(at);
    });
}

// Add "Today" on top when the last separator is not today's, so a fresh message is never
// orphaned under yesterday's heading.
function ensureDay(log) {
    const key = dayKey();
    const days = log.querySelectorAll(".chat-day");
    if (!days.length || days[days.length - 1].dataset.day !== key) {
        const d = el("p", "chat-day", "Today");
        d.dataset.day = key;
        d.dataset.at = new Date().toISOString();
        log.querySelector(".chat-empty")?.remove();
        log.append(d);
    }
}

// Reply buttons on every message; edit/unsend only on the reader's own messages.
// `data-edit-window-minutes` on the chat root drives the Edit hint and its client-side
// guard; the server enforces the same window for edits. Unsending has no time limit.
function editWindowMinutes(root) {
    const n = Number(root?.dataset.editWindowMinutes || 0);
    return Number.isFinite(n) && n > 0 ? n : 0;
}

function messageActions(root, id, own) {
    const wrap = el("span", "chat-msg__actions");
    const add = (action, label, title) => {
        const b = el("button", "chat-msg__action", label);
        b.type = "button";
        b.dataset.chatAction = action;
        b.dataset.chatActionId = String(id);
        if (title) b.title = title;
        wrap.append(b);
    };
    add("reply", "Reply");
    if (own) {
        const win = editWindowMinutes(root);
        const hint =
            win > 0 ? ` (available for ${win} minute${win === 1 ? "" : "s"})` : "";
        add("edit", "Edit", `Edit${hint}`);
        add("unsend", "Unsend");
    }
    return wrap;
}

function replyQuote(name, text) {
    const q = el("div", "chat-msg__quote");
    q.append(el("span", "chat-msg__quote-name", name));
    if (text) q.append(el("span", "chat-msg__quote-text", text));
    return q;
}

function markEdited(msg) {
    const meta = msg.querySelector(".chat-msg__meta");
    if (!meta || meta.querySelector(".chat-msg__edited")) return;
    const marker = el("span", "chat-msg__edited", "edited");
    const actions = meta.querySelector(".chat-msg__actions");
    meta.insertBefore(marker, actions || null);
}

// After an unsend, drop any day heading that no longer has a message under it.
export function tidyDays(log) {
    if (!log) return;
    log.querySelectorAll(".chat-day").forEach((day) => {
        let node = day.nextElementSibling;
        let hasContent = false;
        while (node && !node.classList.contains("chat-day")) {
            if (
                node.classList.contains("chat-msg") ||
                node.classList.contains("chat-booking")
            ) {
                hasContent = true;
                break;
            }
            node = node.nextElementSibling;
        }
        if (!hasContent) day.remove();
    });
}

function attachmentNode(file) {
    const url = URL.createObjectURL(file);
    const isImage = file.type.startsWith("image/");
    const a = el("a", `chat-attach${isImage ? " chat-attach--image" : ""}`);
    a.href = url;
    a.target = "_blank";
    a.rel = "noopener";
    if (isImage) {
        const img = el("img");
        img.src = url;
        img.alt = file.name;
        a.append(img);
    } else {
        a.append(
            icon("file"),
            el("span", "chat-attach__name", file.name),
            el("span", "chat-attach__size", fmtSize(file.size)),
        );
    }
    return a;
}

// Text goes in with textContent only, never innerHTML.
function appendMessage(log, body, files, pendingReply) {
    ensureDay(log);
    const msg = el("div", "chat-msg chat-msg--out");
    msg.dataset.own = "1";
    if (pendingReply) msg.append(replyQuote("You", pendingReply.text));
    const bubble = el("div", "chat-msg__bubble");
    bubble.append(el("span", "visually-hidden", "You: "));
    if (body) bubble.append(el("p", "chat-msg__text", body));
    files.forEach((f) => bubble.append(attachmentNode(f)));
    const meta = el("div", "chat-msg__meta");
    const time = el("time", "chat-msg__time", "Sending…");
    meta.append(time);
    msg.append(bubble, meta);
    log.append(msg);
    scrollLog(log);
    return { msg, time, meta };
}

// Attachment already stored on the server (poll feed): link straight to its URL.
function serverAttachmentNode(file) {
    const isImage =
        file.kind === "image" ||
        /\.(jpe?g|png|webp|gif)$/i.test(file.name || "");
    const a = el("a", `chat-attach${isImage ? " chat-attach--image" : ""}`);
    a.href = file.url || "#";
    a.target = "_blank";
    a.rel = "noopener";
    if (isImage) {
        const img = el("img");
        img.src = file.url || "";
        img.alt = file.name || "Attachment";
        img.loading = "lazy";
        a.append(img);
    } else {
        a.append(
            icon("file"),
            el("span", "chat-attach__name", file.name || "Attachment"),
            el("span", "chat-attach__size", file.size || ""),
        );
    }
    return a;
}

// A message that arrived from the other side (admin -> client). Returns false when
// it is already in the log, so polling can never double-post a message.
export function appendIncoming(log, message, root) {
    if (message.id && log.querySelector(`[data-message-id="${message.id}"]`)) {
        return false;
    }

    ensureDay(log);
    const other = root?.dataset.chatOther || "Them";
    const self = root?.dataset.chatSelf || "client";
    const quoteName = (from) => (from === self ? "You" : other);

    const msg = el("div", "chat-msg chat-msg--in");
    if (message.id) msg.dataset.messageId = message.id;
    if (message.reply_to) {
        msg.append(
            replyQuote(
                quoteName(message.reply_to.from),
                message.reply_to.body,
            ),
        );
    }

    const bubble = el("div", "chat-msg__bubble");
    bubble.append(el("span", "visually-hidden", `${other}: `));
    if (message.body) bubble.append(el("p", "chat-msg__text", message.body));
    (message.attachments || []).forEach((f) =>
        bubble.append(serverAttachmentNode(f)),
    );

    const meta = el("div", "chat-msg__meta");
    const at = message.at ? new Date(message.at) : new Date();
    const time = el("time", "chat-msg__time", formatTime(at));
    time.setAttribute("datetime", at.toISOString());
    meta.append(time);
    if (message.edited) meta.append(el("span", "chat-msg__edited", "edited"));
    if (message.id && root) meta.append(messageActions(root, message.id, false));
    msg.append(bubble, meta);

    log.append(msg);
    scrollLog(log);
    return true;
}

// Apply the current state of a message the reader already has: re-render the text of an
// edited message, or drop one the sender unsent. Returns false when the message is not in
// this log, so a change to something we never showed is ignored (it travels as a new one).
export function applyMessageChange(log, message) {
    if (!log || !message || !message.id) return false;

    const node = log.querySelector(`[data-message-id="${message.id}"]`);
    if (!node) return false;

    if (message.deleted) {
        node.remove();
        tidyDays(log);
        return true;
    }

    const bubble = node.querySelector(".chat-msg__bubble");
    if (!bubble) return false;

    let textEl = bubble.querySelector(".chat-msg__text");
    if (message.body) {
        if (!textEl) {
            textEl = el("p", "chat-msg__text");
            const anchor = bubble.querySelector(".visually-hidden");
            if (anchor) anchor.after(textEl);
            else bubble.prepend(textEl);
        }
        textEl.textContent = message.body;
    } else if (textEl) {
        textEl.remove();
    }

    if (message.edited) markEdited(node);
    return true;
}

// A booking card pushed by the live feed. The server renders the same Blade partial
// the page uses, so the markup (and its escaping) stays in one place. A booking that
// is already in the thread (e.g. it was pending, now approved) is refreshed in place
// instead of being duplicated.
export function appendBooking(log, root, booking) {
    if (!log || !booking || !booking.html) return false;

    const wrap = document.createElement("div");
    wrap.innerHTML = booking.html; // server-rendered, Blade-escaped markup
    const card = wrap.firstElementChild;
    if (!card) return false;
    if (booking.id) card.dataset.bookingId = booking.id;

    const existing = booking.id
        ? log.querySelector(`[data-booking-id="${booking.id}"]`)
        : null;

    card.classList.add("is-new");
    if (existing) {
        existing.replaceWith(card);
    } else {
        ensureDay(log);
        log.append(card);
    }
    window.setTimeout(() => card.classList.remove("is-new"), 2600);
    scrollLog(log);
    return true;
}

// Live updates for an open chat: polls `data-chat-updates-url` for new admin
// replies, updates every unread badge, and marks the thread read when the chat is
// open. Safe on any chat root: no URL, no polling, nothing happens.
export function initChatStream(root) {
    if (!root || root.dataset.chatStreamReady === "1") return;
    root.dataset.chatStreamReady = "1";

    const log = root.querySelector("[data-chat-log]");
    const form = root.querySelector("[data-chat-form]");
    const updatesUrl = root.dataset.chatUpdatesUrl || "";
    const readUrl = root.dataset.chatReadUrl || "";
    // Cursor for the booking feed: bookings changed after this moment pop in. It
    // starts at page render and advances to the server clock on every poll.
    let since = root.dataset.chatBookingsSince || "";
    // Cursor for the edit/unsend feed: messages changed after this moment are re-applied.
    let changedSince = root.dataset.chatMessagesSince || "";

    const setBadge = (count) => {
        const text = count > 9 ? "9+" : String(count);
        const hidden = !count;
        root.querySelectorAll("[data-chat-badge]").forEach((b) => {
            b.textContent = text;
            b.hidden = hidden;
        });
        document.querySelectorAll("[data-chat-unread-badge]").forEach((b) => {
            b.textContent = text;
            b.hidden = hidden;
        });
    };

    const lastId = () =>
        [...(log?.querySelectorAll("[data-message-id]") || [])].reduce(
            (max, node) => Math.max(max, Number(node.dataset.messageId) || 0),
            0,
        );

    const isOpen = () => {
        const panel = root.querySelector("[data-chat-panel]");
        return panel ? !panel.hidden : true;
    };

    const markRead = () => {
        if (!readUrl) return;
        fetch(readUrl, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": getCsrfToken(form),
            },
        })
            .then((res) => (res.ok ? res.json().catch(() => ({})) : {}))
            .then(() => setBadge(0))
            .catch(() => {});
    };

    let busy = false;
    const poll = () => {
        if (!log || !updatesUrl || busy || document.hidden) return;
        busy = true;
        fetch(
            `${updatesUrl}?after=${lastId()}&since=${encodeURIComponent(since)}&changed_since=${encodeURIComponent(changedSince)}`,
            { headers: { Accept: "application/json" } },
        )
            .then((res) => (res.ok ? res.json() : null))
            .then((data) => {
                if (!data) return;
                let added = 0;
                (data.messages || []).forEach((m) => {
                    if (appendIncoming(log, m, root)) added += 1;
                });
                // Edits and unsends of messages already in the thread.
                (data.messages_changed || []).forEach((m) =>
                    applyMessageChange(log, m),
                );
                (data.bookings || []).forEach((booking) =>
                    appendBooking(log, root, booking),
                );
                if (data.bookings_synced_at) since = data.bookings_synced_at;
                if (data.messages_synced_at)
                    changedSince = data.messages_synced_at;
                setBadge(data.unread || 0);
                if (added && isOpen()) markRead();
            })
            .catch(() => {})
            .finally(() => {
                busy = false;
            });
    };

    root.addEventListener("chat:opened", markRead);
    setInterval(poll, 5000);
    poll();
}

export function initChat(root) {
    const form = root.querySelector("[data-chat-form]");
    const log = root.querySelector("[data-chat-log]");
    if (!form || !log) return;

    // Server-rendered times are UTC; show them in the visitor's timezone on load.
    localizeTimes(log);

    const input = form.querySelector("[data-chat-input]");
    const picker = form.querySelector("[data-chat-picker]");
    const list = form.querySelector("[data-chat-files]");
    const error = form.querySelector("[data-chat-error]");
    const replyBar = form.querySelector("[data-chat-reply]");
    const replyWho = form.querySelector("[data-chat-reply-who]");
    const replyText = form.querySelector("[data-chat-reply-text]");
    const replyCancel = form.querySelector("[data-chat-reply-cancel]");
    const other = root.dataset.chatOther || "Them";
    const max = {
        files: +form.dataset.maxFiles,
        bytes: +form.dataset.maxBytes,
        types: form.dataset.accept.split(","),
    };
    let files = [];
    let reply = null;

    const setError = (text) => {
        error.textContent = text || "";
        error.hidden = !text;
    };
    const autosize = () => {
        input.style.height = "auto";
        input.style.height = `${Math.min(input.scrollHeight, 128)}px`;
    };

    const clearReply = () => {
        reply = null;
        if (replyBar) replyBar.hidden = true;
    };
    const showReply = (target) => {
        reply = target;
        if (!replyBar) return;
        replyWho.textContent = target.own ? "your message" : other;
        replyText.textContent = target.text;
        replyBar.hidden = false;
    };
    replyCancel?.addEventListener("click", clearReply);

    function renderFiles() {
        list.replaceChildren(
            ...files.map((f, i) => {
                const li = el("li", null, `${f.name} (${fmtSize(f.size)})`);
                const rm = el("button");
                rm.type = "button";
                rm.setAttribute("aria-label", `Remove ${f.name}`);
                rm.append(icon("x"));
                rm.addEventListener("click", () => {
                    files.splice(i, 1);
                    renderFiles();
                    input.focus();
                });
                li.append(rm);
                return li;
            }),
        );
        list.hidden = !files.length;
    }

    picker.addEventListener("change", () => {
        setError("");
        for (const f of picker.files) {
            if (files.length >= max.files) {
                setError(`You can attach up to ${max.files} files.`);
                break;
            }
            if (!max.types.includes(f.type)) {
                setError(
                    `${f.name} is not supported. Use JPG, PNG, WEBP or PDF.`,
                );
                continue;
            }
            if (f.size > max.bytes) {
                setError(`${f.name} is larger than ${fmtSize(max.bytes)}.`);
                continue;
            }
            files.push(f);
        }
        picker.value = "";
        renderFiles();
    });

    input.addEventListener("input", autosize);
    input.addEventListener("keydown", (e) => {
        if (e.key === "Enter" && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    // ---- reply / edit / unsend -------------------------------------------------

    function startReply(msg, id) {
        const textEl = msg.querySelector(".chat-msg__text");
        const text = textEl
            ? textEl.textContent.trim()
            : msg.querySelector(".chat-attach")
              ? "Attachment"
              : "message";
        showReply({ id, text, own: msg.dataset.own === "1" });
        input.focus();
    }

    function startEdit(msg, id) {
        if (msg.querySelector(".chat-edit")) return;
        const bubble = msg.querySelector(".chat-msg__bubble");
        if (!bubble) return;

        const editor = el("form", "chat-edit");
        const textarea = el("textarea", "chat-edit__input");
        textarea.rows = 2;
        if (input.maxLength > 0) textarea.maxLength = input.maxLength;
        textarea.value = msg.querySelector(".chat-msg__text")?.textContent || "";
        // Enter saves the edit (Shift+Enter inserts a newline), same as the composer.
        textarea.addEventListener("keydown", (e) => {
            if (e.key === "Enter" && !e.shiftKey && !e.isComposing) {
                e.preventDefault();
                editor.requestSubmit();
            }
        });
        const row = el("div", "chat-edit__row");
        const save = el("button", "chat-edit__save", "Save");
        save.type = "submit";
        const cancel = el("button", "chat-edit__cancel", "Cancel");
        cancel.type = "button";
        row.append(save, cancel);
        editor.append(textarea);

        // How long the sender still has to edit, straight from the server window.
        const until = msg.dataset.editableUntil
            ? new Date(msg.dataset.editableUntil)
            : null;
        if (until && !Number.isNaN(until.getTime())) {
            const mins = Math.round((until.getTime() - Date.now()) / 60000);
            editor.append(
                el(
                    "p",
                    "chat-edit__hint",
                    mins >= 1
                        ? `You can edit this message for about ${mins} more minute${mins === 1 ? "" : "s"}.`
                        : "You can only edit this message for a few more seconds.",
                ),
            );
        }

        editor.append(row);

        const close = () => {
            editor.remove();
            bubble.hidden = false;
        };
        cancel.addEventListener("click", close);

        editor.addEventListener("submit", async (e) => {
            e.preventDefault();
            const body = textarea.value.trim();
            if (!body) {
                textarea.focus();
                return;
            }
            const url = messageUrl(root.dataset.chatUpdateUrl, id);
            if (!url) return;
            save.disabled = true;
            try {
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": getCsrfToken(form),
                    },
                    body: JSON.stringify({ body }),
                });
                if (!res.ok) {
                    const json = await res.json().catch(() => ({}));
                    throw new Error(json.message || "");
                }
                let textEl = bubble.querySelector(".chat-msg__text");
                if (!textEl) {
                    textEl = el("p", "chat-msg__text");
                    bubble.insertBefore(
                        textEl,
                        bubble.querySelector(".visually-hidden")?.nextSibling ||
                            null,
                    );
                }
                textEl.textContent = body;
                markEdited(msg);
                close();
            } catch (err) {
                save.disabled = false;
                setError(err.message || "Message not updated. Please try again.");
            }
        });

        bubble.hidden = true;
        msg.insertBefore(editor, bubble.nextSibling);
        textarea.focus();
        textarea.setSelectionRange(textarea.value.length, textarea.value.length);
    }

    async function unsend(msg, id) {
        if (!window.confirm("Unsend this message?")) return;
        const url = messageUrl(root.dataset.chatDeleteUrl, id);
        if (!url) return;
        try {
            const res = await fetch(url, {
                method: "DELETE",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(form),
                },
            });
            if (!res.ok) {
                const json = await res.json().catch(() => ({}));
                throw new Error(json.message || "");
            }
            msg.remove();
            tidyDays(log);
        } catch (err) {
            setError(err.message || "Message not unsent. Please try again.");
        }
    }

    // Editing past the server window is refused; catch it here first for a clear message.
    // Unsending is not time-limited, so it is never blocked here.
    const editWindowPassed = (msg) => {
        const until = msg.dataset.editableUntil;
        if (!until) return false;
        const at = new Date(until);
        return !Number.isNaN(at.getTime()) && at.getTime() < Date.now();
    };

    root.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-chat-action]");
        if (!btn || !root.contains(btn)) return;
        const msg = btn.closest(".chat-msg");
        const id = btn.dataset.chatActionId;
        if (!msg || !id) return;
        if (btn.dataset.chatAction === "reply") startReply(msg, id);
        else if (btn.dataset.chatAction === "edit") {
            if (editWindowPassed(msg)) {
                setError("The editing window for this message has passed.");
                return;
            }
            startEdit(msg, id);
        } else if (btn.dataset.chatAction === "unsend") {
            unsend(msg, id);
        }
    });

    // ---- send ------------------------------------------------------------------

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body && !files.length) return;

        setError("");
        const sent = files;
        const pendingReply = reply;
        files = [];
        renderFiles();
        input.value = "";
        autosize();
        clearReply();

        const { msg, time, meta } = appendMessage(
            log,
            body,
            sent,
            pendingReply,
        );
        const data = new FormData();
        if (body) data.append("body", body);
        if (pendingReply) data.append("reply_to_id", pendingReply.id);
        sent.forEach((f) => data.append("attachments[]", f));

        try {
            const res = await fetch(form.action, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(form),
                },
                body: data,
            });
            if (!res.ok) {
                const json = await res.json().catch(() => ({}));
                throw Object.assign(new Error(json.message || ""), {
                    status: res.status,
                });
            }
            const json = await res.json().catch(() => ({}));
            const now = new Date();
            time.textContent = timeLabel();
            time.setAttribute("datetime", now.toISOString());
            if (json?.message?.id) {
                msg.dataset.messageId = json.message.id;
                if (json.message.edit_expires_at) {
                    msg.dataset.editableUntil = json.message.edit_expires_at;
                }
                meta.append(messageActions(root, json.message.id, true));
            }
            root.dispatchEvent(
                new CustomEvent("chat:sent", {
                    bubbles: true,
                    detail: { body, files: sent.length },
                }),
            );
        } catch (err) {
            // Nothing is lost: take the bubble back and restore the draft.
            msg.remove();
            input.value = body;
            files = sent;
            renderFiles();
            autosize();
            if (pendingReply) showReply(pendingReply);
            setError(
                err.status === 422 && err.message
                    ? err.message
                    : "Message not sent. Check your connection and try again.",
            );
        }
    });
}
