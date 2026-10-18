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
    more: '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
    "arrow-down": '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>',
};
const icon = (name) => {
    const t = document.createElement("template");
    t.innerHTML = `<svg class="chat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${ICONS[name]}</svg>`;
    return t.content.firstChild;
};
// "Unsend this message?" dialog (a real modal <dialog>: focus stays inside, Esc and a click
// outside cancel, the page behind is dimmed). Resolves true only when "Unsend" is pressed.
// The server removes a message for everyone, so there is a single option; browsers without
// <dialog> fall back to window.confirm().
function confirmUnsend() {
    if (typeof HTMLDialogElement === "undefined") {
        return Promise.resolve(window.confirm("Unsend this message?"));
    }
    return new Promise((resolve) => {
        const dialog = el("dialog", "chat-modal");
        dialog.setAttribute("aria-labelledby", "chatUnsendTitle");
        dialog.setAttribute("aria-describedby", "chatUnsendText");

        const head = el("div", "chat-modal__head");
        const title = el("h2", "chat-modal__title", "Unsend this message?");
        title.id = "chatUnsendTitle";
        const close = el("button", "chat-modal__close");
        close.type = "button";
        close.setAttribute("aria-label", "Close");
        close.append(icon("x"));
        head.append(title, close);

        const body = el(
            "p",
            "chat-modal__text",
            "This message will be unsent for everyone in the chat. They may have already seen it.",
        );
        body.id = "chatUnsendText";

        const foot = el("div", "chat-modal__foot");
        const cancel = el("button", "chat-modal__cancel", "Cancel");
        cancel.type = "button";
        cancel.autofocus = true;
        const confirm = el("button", "chat-modal__confirm", "Unsend");
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
const fmtSize = (b) =>
    b < 1048576
        ? `${Math.max(1, Math.round(b / 1024))} KB`
        : `${(b / 1048576).toFixed(1)} MB`;
// Full stamp (date + time): chat-list rows and the hover title on every message time.
export const formatStamp = (date) =>
    date.toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
// Inside a thread only the clock time is shown ("7:37 PM"): the Today / Yesterday / date divider
// above it carries the day, and the full stamp stays in the element's title.
export const formatTime = (date) =>
    date.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" });
// Chat-list rows: "7:45 PM" today, "Yesterday", "Oct 15", "Oct 15, 2025" (full stamp in the title).
export function formatListStamp(date) {
    const now = new Date();
    if (dayKeyOf(date) === dayKeyOf(now)) return formatTime(date);
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    if (dayKeyOf(date) === dayKeyOf(yesterday)) return "Yesterday";
    return date.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        ...(date.getFullYear() === now.getFullYear()
            ? {}
            : { year: "numeric" }),
    });
}
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

// ---- scroll position ------------------------------------------------------------
// Is the reader on the newest message (or within a few lines of it)?
const nearBottom = (log, slack = 48) =>
    log.scrollHeight - log.scrollTop - log.clientHeight < slack;

// log -> true while the reader is following the newest message. Kept in a WeakMap so the
// resize observer (initChat) and the unread divider can share it.
const pinOf = new WeakMap();

export const scrollLog = (log) => {
    log.scrollTop = log.scrollHeight;
    pinOf.set(log, true);
    resetPill(log);
};

// "↓ N new messages" pill: shown when something arrives while the reader has scrolled up,
// instead of yanking them to the bottom. It sits in a zero-height slot between the thread and
// the composer, so it floats over the bottom of the thread without moving anything.
const pills = new WeakMap();

function pillFor(log) {
    let state = pills.get(log);
    if (state) return state;
    const slot = el("div", "chat-newpill-slot");
    const btn = el("button", "chat-newpill");
    btn.type = "button";
    btn.hidden = true;
    btn.append(icon("arrow-down"), el("span", "chat-newpill__label"));
    slot.append(btn);
    log.after(slot);
    state = { btn, label: btn.querySelector(".chat-newpill__label"), count: 0 };
    pills.set(log, state);
    btn.addEventListener("click", () => {
        scrollLog(log);
        log.focus({ preventScroll: true });
    });
    // Reaching the bottom by hand dismisses it.
    log.addEventListener(
        "scroll",
        () => {
            if (nearBottom(log)) resetPill(log);
        },
        { passive: true },
    );
    return state;
}

function resetPill(log) {
    const state = pills.get(log);
    if (!state) return;
    state.count = 0;
    state.btn.hidden = true;
}

function noteNew(log) {
    const state = pillFor(log);
    state.count += 1;
    state.label.textContent =
        state.count === 1 ? "1 new message" : `${state.count} new messages`;
    state.btn.hidden = false;
}

// A "New messages" divider above the first unread message, and the thread scrolled to it.
// `unread` is how many of the other side's latest messages are still unread (the unread
// badge); they are always the newest ones. Returns false when there is nothing to mark, so the
// caller falls back to scrolling to the bottom. Call it once the log is visible.
export function showUnreadDivider(log, unread) {
    if (!log) return false;
    log.querySelector(".chat-unread")?.remove();
    const incoming = [...log.querySelectorAll(".chat-msg--in")];
    const n = Math.min(Math.max(Number(unread) || 0, 0), incoming.length);
    if (!n) return false;

    const mark = el("div", "chat-unread");
    mark.setAttribute("role", "separator");
    mark.append(el("span", "chat-unread__label", "New messages"));
    incoming[incoming.length - n].before(mark);

    // Start at the bottom; only if the divider is then out of sight, bring it to the top.
    scrollLog(log);
    const gap =
        mark.getBoundingClientRect().top - log.getBoundingClientRect().top;
    if (gap < 0) {
        log.scrollTop += gap - 8;
        pinOf.set(log, false);
    }
    return true;
}

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
        time.title = formatStamp(at);
    });
}

// ---- grouping ---------------------------------------------------------------
// Consecutive messages from the same side, sent within a few minutes of each other, form a run.
// Each message gets data-group = single | first | middle | last; the CSS tightens the spacing,
// joins the corners and shows the time once, under the last bubble of the run.
const GROUP_GAP_MS = 5 * 60 * 1000;

export function groupMessages(log) {
    if (!log) return;
    const run = [];
    const flush = () => {
        run.forEach((node, i) => {
            node.dataset.group =
                run.length === 1
                    ? "single"
                    : i === 0
                      ? "first"
                      : i === run.length - 1
                        ? "last"
                        : "middle";
        });
        run.length = 0;
    };
    let prevDir = "";
    let prevAt = 0;
    for (const node of log.children) {
        // Day dividers and booking cards break a run.
        if (!node.classList.contains("chat-msg")) {
            flush();
            prevDir = "";
            continue;
        }
        const dir = node.classList.contains("chat-msg--out") ? "out" : "in";
        // A message still sending has no datetime yet: treat it as "now".
        const stamp = node.querySelector("time[datetime]");
        const at = stamp
            ? new Date(stamp.getAttribute("datetime")).getTime()
            : Date.now();
        if (
            run.length &&
            (dir !== prevDir || Math.abs(at - prevAt) > GROUP_GAP_MS)
        ) {
            flush();
        }
        run.push(node);
        prevDir = dir;
        prevAt = at;
    }
    flush();
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

// True once the server edit window for this message has passed.
function editWindowPassed(msg) {
    const until = msg.dataset.editableUntil;
    if (!until) return false;
    const at = new Date(until);
    return !Number.isNaN(at.getTime()) && at.getTime() < Date.now();
}

// One "⋯" button beside the bubble opens a small menu: Reply, Edit, Unsend. Same markup as
// thread.blade.php; the data-chat-action hooks are unchanged. Lives inside the bubble, so
// editing (which hides the bubble) hides the menu with it.
function messageActions(root, id, own) {
    const wrap = el("div", "chat-msg__actions");

    const toggle = el("button", "chat-msg__more");
    toggle.type = "button";
    toggle.dataset.chatMenuToggle = "";
    toggle.setAttribute("aria-haspopup", "menu");
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-label", "Message options");
    toggle.append(icon("more"));

    const menu = el("div", "chat-msg__menu");
    menu.setAttribute("role", "menu");
    menu.setAttribute("aria-label", "Message options");
    menu.hidden = true;

    const add = (action, label, title) => {
        const b = el("button", "chat-msg__action", label);
        b.type = "button";
        b.setAttribute("role", "menuitem");
        b.dataset.chatAction = action;
        b.dataset.chatActionId = String(id);
        if (title) b.title = title;
        menu.append(b);
    };
    add("reply", "Reply");
    if (own) {
        const win = editWindowMinutes(root);
        const hint =
            win > 0
                ? ` (available for ${win} minute${win === 1 ? "" : "s"})`
                : "";
        add("edit", "Edit", `Edit${hint}`);
        add("unsend", "Unsend");
    }

    wrap.append(toggle, menu);
    return wrap;
}

// ---- message options menu -----------------------------------------------------
function closeMenus(except = null) {
    document.querySelectorAll(".chat-msg.is-menu-open").forEach((msg) => {
        if (msg === except) return;
        msg.classList.remove("is-menu-open");
        const menu = msg.querySelector(".chat-msg__menu");
        if (menu) {
            menu.hidden = true;
            menu.classList.remove("is-up");
        }
        msg.querySelector("[data-chat-menu-toggle]")?.setAttribute(
            "aria-expanded",
            "false",
        );
    });
}

function openMenu(msg, { focusFirst = false } = {}) {
    const menu = msg.querySelector(".chat-msg__menu");
    const bubble = msg.querySelector(".chat-msg__bubble");
    if (!menu || !bubble) return;
    closeMenus(msg);

    // The edit window may have closed since the message was sent: grey the item out.
    const edit = menu.querySelector('[data-chat-action="edit"]');
    if (edit) {
        edit.dataset.title ??= edit.title;
        const passed = editWindowPassed(msg);
        edit.disabled = passed;
        edit.title = passed
            ? "The editing window for this message has passed."
            : edit.dataset.title;
    }

    msg.classList.add("is-menu-open");
    menu.hidden = false;
    menu.classList.remove("is-up");

    // Open upward when there is no room below (the newest messages sit at the log's bottom edge).
    const log = msg.closest("[data-chat-log]");
    if (log) {
        const box = log.getBoundingClientRect();
        const rect = bubble.getBoundingClientRect();
        const below = box.bottom - rect.bottom;
        const above = rect.top - box.top;
        if (below < menu.offsetHeight + 8 && above > below) {
            menu.classList.add("is-up");
        }
    }
    msg.querySelector("[data-chat-menu-toggle]")?.setAttribute(
        "aria-expanded",
        "true",
    );
    if (focusFirst) menu.querySelector("button:not(:disabled)")?.focus();
}

let menuListenersReady = false;
function initMenuListeners() {
    if (menuListenersReady) return;
    menuListenersReady = true;
    // A click anywhere outside the open message closes its menu.
    document.addEventListener("click", (e) => {
        if (!e.target.closest(".chat-msg.is-menu-open")) closeMenus();
    });
}

// "You replied to Mark" / "Mark replied to you" (same wording as thread.blade.php).
function quoteLabel(out, quotedMine, other) {
    if (out)
        return quotedMine
            ? "You replied to yourself"
            : `You replied to ${other}`;
    return quotedMine
        ? `${other} replied to you`
        : `${other} replied to themselves`;
}

// A reply quote is a button: click / Enter jumps to the original message (data-reply-id).
function replyQuote(label, text, replyId) {
    const q = el("div", "chat-msg__quote");
    q.setAttribute("role", "button");
    q.tabIndex = 0;
    q.title = "Go to the original message";
    if (replyId) q.dataset.replyId = String(replyId);
    q.append(el("span", "chat-msg__quote-name", label));
    if (text) q.append(el("span", "chat-msg__quote-text", text));
    return q;
}

// Scroll the thread (not the page) so `target` sits in the middle, and flash it briefly.
function jumpTo(log, target) {
    if (!log || !target) return false;
    const reduce = window.matchMedia(
        "(prefers-reduced-motion: reduce)",
    ).matches;
    const box = log.getBoundingClientRect();
    const rect = target.getBoundingClientRect();
    log.scrollBy({
        top: rect.top - box.top - (log.clientHeight - rect.height) / 2,
        behavior: reduce ? "auto" : "smooth",
    });
    target.classList.add("is-flash");
    window.setTimeout(() => target.classList.remove("is-flash"), 1600);
    return true;
}

// A quote whose original message was unsent: say so instead of quoting text that is gone.
function refreshQuotes(log) {
    log.querySelectorAll(".chat-msg__quote[data-reply-id]").forEach((q) => {
        if (q.classList.contains("is-gone")) return;
        if (log.querySelector(`[data-message-id="${q.dataset.replyId}"]`))
            return;
        q.classList.add("is-gone");
        q.removeAttribute("role");
        q.removeAttribute("tabindex");
        q.removeAttribute("title");
        const text = q.querySelector(".chat-msg__quote-text");
        if (text) text.textContent = "Message unsent";
        else q.append(el("span", "chat-msg__quote-text", "Message unsent"));
    });
}

// ---- booking strip (user widget) -----------------------------------------------------
// A slim bar under the header with the client's current booking (package, date, status);
// clicking it scrolls to the full card. Opt-in with data-chat-booking-strip on the chat root.
function initBookingStrip(root, log) {
    if (!root.hasAttribute("data-chat-booking-strip")) return () => {};
    const strip = el("button", "chat-strip");
    strip.type = "button";
    strip.hidden = true;
    const main = el("span", "chat-strip__main");
    strip.append(el("span", "chat-strip__label", "Your booking"), main);
    log.before(strip);
    let target = null;
    strip.addEventListener("click", () => jumpTo(log, target));
    return () => {
        const cards = [...log.querySelectorAll(".chat-booking:not(.is-past)")];
        target = cards[cards.length - 1] || null;
        if (!target) {
            strip.hidden = true;
            return;
        }
        const name =
            target
                .querySelector(".chat-booking__package")
                ?.textContent.trim() || "Booking";
        const when =
            target
                .querySelectorAll(".chat-booking__details dd")[1]
                ?.textContent.trim() || "";
        main.replaceChildren(
            el("span", "chat-strip__pkg", name),
            el("span", "chat-strip__when", when),
        );
        strip.querySelector(".chat-status")?.remove();
        const status = target.querySelector(".chat-status");
        if (status) strip.append(status.cloneNode(true));
        strip.hidden = false;
    };
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
function appendMessage(log, body, files, pendingReply, other = "them") {
    ensureDay(log);
    const msg = el("div", "chat-msg chat-msg--out");
    msg.dataset.own = "1";
    if (pendingReply) {
        msg.append(
            replyQuote(
                quoteLabel(true, pendingReply.own, other),
                pendingReply.text,
                pendingReply.id,
            ),
        );
    }
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
    return { msg, time, meta, bubble };
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

    // Follow the newest message only if the reader is already there.
    const stick = nearBottom(log);
    ensureDay(log);
    const other = root?.dataset.chatOther || "Them";
    const self = root?.dataset.chatSelf || "client";

    const msg = el("div", "chat-msg chat-msg--in");
    if (message.id) msg.dataset.messageId = message.id;
    if (message.reply_to) {
        msg.append(
            replyQuote(
                quoteLabel(false, message.reply_to.from === self, other),
                message.reply_to.body,
                message.reply_to.id,
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
    time.title = formatStamp(at);
    meta.append(time);
    if (message.edited) meta.append(el("span", "chat-msg__edited", "edited"));
    if (message.id && root)
        bubble.append(messageActions(root, message.id, false));
    msg.append(bubble, meta);

    log.append(msg);
    if (stick) scrollLog(log);
    else noteNew(log);
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
    const stick = nearBottom(log);

    card.classList.add("is-new");
    if (existing) {
        existing.replaceWith(card);
    } else {
        ensureDay(log);
        log.append(card);
    }
    window.setTimeout(() => card.classList.remove("is-new"), 2600);
    if (stick) scrollLog(log);
    else if (!existing) noteNew(log);
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

    // Polling pace: fast while the chat is open and the window has focus, slow when it is open
    // but in the background or the widget is closed (the badge still updates), paused while the
    // tab is hidden. A failed poll backs off, up to a minute.
    const FAST_MS = 3000;
    const SLOW_MS = 15000;
    const MAX_MS = 60000;
    let busy = false;
    let timer = 0;
    let failures = 0;

    const delay = () => {
        const base = isOpen() && document.hasFocus() ? FAST_MS : SLOW_MS;
        return Math.min(base * 2 ** failures, MAX_MS);
    };
    const schedule = () => {
        window.clearTimeout(timer);
        if (!updatesUrl || document.hidden) return; // resumes on visibilitychange
        timer = window.setTimeout(poll, delay());
    };

    const poll = () => {
        if (!log || !updatesUrl || document.hidden) return;
        if (busy) return;
        busy = true;
        window.clearTimeout(timer);
        fetch(
            `${updatesUrl}?after=${lastId()}&since=${encodeURIComponent(since)}&changed_since=${encodeURIComponent(changedSince)}`,
            { headers: { Accept: "application/json" } },
        )
            .then((res) => {
                if (!res.ok) throw new Error(String(res.status));
                return res.json();
            })
            .then((data) => {
                failures = 0;
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
            .catch(() => {
                failures = Math.min(failures + 1, 4);
            })
            .finally(() => {
                busy = false;
                schedule();
            });
    };

    // Back in the tab, or the window regained focus: catch up now, then pick the right pace.
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
    root.addEventListener("chat:opened", () => {
        markRead();
        poll();
        schedule();
    });
    root.addEventListener("chat:closed", schedule);

    poll();
    schedule();
}

export function initChat(root) {
    const form = root.querySelector("[data-chat-form]");
    const log = root.querySelector("[data-chat-log]");
    if (!form || !log) return;

    // Server-rendered times are UTC; show them in the visitor's timezone on load.
    localizeTimes(log);

    // Group runs of messages (spacing, corners, one time stamp per run). The observer keeps
    // it right for sent, polled, edited and unsent messages without touching those code paths.
    const refreshStrip = initBookingStrip(root, log);
    const tidyThread = () => {
        groupMessages(log);
        refreshQuotes(log);
        refreshStrip();
    };
    tidyThread();
    let groupQueued = false;
    new MutationObserver(() => {
        if (groupQueued) return;
        groupQueued = true;
        requestAnimationFrame(() => {
            groupQueued = false;
            tidyThread();
        });
    }).observe(log, { childList: true });

    // Stay on the newest message while the thread changes size (the composer growing, the reply
    // bar opening). Someone who scrolled up to read is left where they are.
    log.addEventListener(
        "scroll",
        () => {
            pinOf.set(log, nearBottom(log));
        },
        { passive: true },
    );
    new ResizeObserver(() => {
        if (pinOf.get(log) ?? true) scrollLog(log);
    }).observe(log);

    initMenuListeners();
    const touchOnly = window.matchMedia("(hover: none)");

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
    // The height cap lives in CSS (max-height on .chat-composer__input); this follows it.
    const autosize = () => {
        const style = getComputedStyle(input);
        const cap = parseFloat(style.maxHeight) || 128;
        input.style.height = "auto";
        input.style.height = `${Math.min(input.scrollHeight, cap)}px`;
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

    // ---- edit mode: the message box itself edits the message -------------------------------
    // "Edit" loads the message text into the box, shows the edit bar above it and highlights the
    // bubble. Send saves (PATCH, same URL and body as before), the X or Esc cancels. Whatever was
    // typed before is put back afterwards.
    const editBar = form.querySelector("[data-chat-edit-bar]");
    const editHint = form.querySelector("[data-chat-edit-hint]");
    const editCancel = form.querySelector("[data-chat-edit-cancel]");
    let editing = null; // { msg, id, draft }
    const endEdit = () => {
        if (!editing) return;
        const { msg, draft } = editing;
        editing = null;
        msg.classList.remove("is-editing");
        form.classList.remove("is-editing");
        if (editBar) editBar.hidden = true;
        input.value = draft;
        autosize();
    };
    editCancel?.addEventListener("click", () => {
        endEdit();
        input.focus();
    });

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

    // ---- quick replies (admin): a "⚡" button opens a popover with the full texts --------
    // Picking one fills the box (messages.js does the filling via data-chat-quick) and closes the
    // popover; nothing is sent until the admin presses Send.
    const quickToggle = form.querySelector("[data-chat-quick-toggle]");
    const quickMenu = form.querySelector("[data-chat-quick-menu]");
    const setQuick = (open, { focusFirst = false } = {}) => {
        if (!quickToggle || !quickMenu) return;
        quickMenu.hidden = !open;
        quickToggle.setAttribute("aria-expanded", String(open));
        if (open) {
            closeMenus();
            if (focusFirst) quickMenu.querySelector("button")?.focus();
        }
    };
    quickToggle?.addEventListener("click", (e) =>
        setQuick(quickMenu.hidden, { focusFirst: e.detail === 0 }),
    );
    quickMenu?.addEventListener("click", (e) => {
        const chip = e.target.closest("[data-chat-quick]");
        if (!chip) return;
        // Fill the box; nothing is sent until the sender presses Send.
        input.value = chip.dataset.chatQuick;
        input.dispatchEvent(new Event("input"));
        setQuick(false);
        input.focus();
    });
    document.addEventListener("click", (e) => {
        if (!quickMenu || quickMenu.hidden) return;
        if (
            !e.target.closest(
                "[data-chat-quick-toggle], [data-chat-quick-menu]",
            )
        ) {
            setQuick(false);
        }
    });

    // ---- phones: collapse the side buttons while typing (like Messenger) ----------------
    // Focusing the box hides "attach" and "⚡" behind a chevron so the box gets the width; the
    // chevron brings them back. They also return when focus leaves the composer. CSS only
    // applies .is-compact on screens up to 575.98px, so wider screens are unchanged.
    const expandBtn = form.querySelector("[data-chat-expand]");
    const narrow = window.matchMedia("(max-width: 575.98px)");
    let expandedByUser = false;
    const setCompact = (on) => {
        const compact = on && narrow.matches;
        form.classList.toggle("is-compact", compact);
        expandBtn?.setAttribute("aria-expanded", String(!compact));
        if (compact) setQuick(false);
    };
    input.addEventListener("focus", () => {
        if (!expandedByUser) setCompact(true);
    });
    expandBtn?.addEventListener("click", () => {
        expandedByUser = true;
        setCompact(false);
        input.focus(); // keep the keyboard open
    });
    // Keep the box focused when the chevron is pressed, otherwise the blur would re-expand first.
    ["pointerdown", "mousedown"].forEach((type) =>
        expandBtn?.addEventListener(type, (e) => e.preventDefault()),
    );
    form.addEventListener("focusout", (e) => {
        if (form.contains(e.relatedTarget)) return;
        expandedByUser = false;
        setCompact(false);
    });
    narrow.addEventListener("change", () => setCompact(false));

    function startReply(msg, id) {
        endEdit(); // replying and editing never run at the same time
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
        if (editing?.msg === msg) {
            input.focus();
            return;
        }
        const bubble = msg.querySelector(".chat-msg__bubble");
        if (!bubble) return;
        endEdit();
        clearReply();
        setError("");

        editing = { msg, id, draft: input.value };
        msg.classList.add("is-editing");
        form.classList.add("is-editing");

        // How long the sender still has to edit, straight from the server window.
        let hint = "";
        const until = msg.dataset.editableUntil
            ? new Date(msg.dataset.editableUntil)
            : null;
        if (until && !Number.isNaN(until.getTime())) {
            const mins = Math.round((until.getTime() - Date.now()) / 60000);
            hint =
                mins >= 1
                    ? `You can edit this message for about ${mins} more minute${mins === 1 ? "" : "s"}.`
                    : "You can only edit this message for a few more seconds.";
        }
        if (editHint) editHint.textContent = hint;
        if (editBar) editBar.hidden = false;

        input.value = msg.querySelector(".chat-msg__text")?.textContent || "";
        autosize();
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
    }

    async function saveEdit() {
        const { msg, id } = editing;
        const bubble = msg.querySelector(".chat-msg__bubble");
        const body = input.value.trim();
        if (!body) {
            input.focus();
            return;
        }
        const url = messageUrl(root.dataset.chatUpdateUrl, id);
        if (!url) return;
        setError("");
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
            endEdit();
        } catch (err) {
            setError(err.message || "Message not updated. Please try again.");
        }
    }

    async function unsend(msg, id) {
        if (!(await confirmUnsend())) return;
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
            if (editing?.msg === msg) endEdit();
            msg.remove();
            tidyDays(log);
        } catch (err) {
            setError(err.message || "Message not unsent. Please try again.");
        }
    }

    // Editing past the server window is refused; the menu greys Edit out, and this guard
    // (editWindowPassed, above) still gives a clear message. Unsending is never time-limited.
    root.addEventListener("click", (e) => {
        // Reply quote: jump to the original message.
        const quote = e.target.closest(".chat-msg__quote[data-reply-id]");
        if (
            quote &&
            root.contains(quote) &&
            !quote.classList.contains("is-gone")
        ) {
            jumpTo(
                log,
                log.querySelector(
                    `[data-message-id="${quote.dataset.replyId}"]`,
                ),
            );
            return;
        }

        // Empty-state suggestion: drop it into the box for the client to edit and send.
        const starter = e.target.closest("[data-chat-starter]");
        if (starter && root.contains(starter)) {
            input.value = starter.dataset.chatStarter;
            input.dispatchEvent(new Event("input"));
            input.focus();
            return;
        }

        // "⋯" button: open / close this message's menu (keyboard opens focus the first item).
        const toggle = e.target.closest("[data-chat-menu-toggle]");
        if (toggle && root.contains(toggle)) {
            const owner = toggle.closest(".chat-msg");
            if (owner.classList.contains("is-menu-open")) closeMenus();
            else openMenu(owner, { focusFirst: e.detail === 0 });
            return;
        }

        const btn = e.target.closest("[data-chat-action]");
        if (!btn || !root.contains(btn)) {
            // Touch screens have no hover: tapping a bubble opens its menu instead.
            const bubble = e.target.closest(".chat-msg__bubble");
            if (
                touchOnly.matches &&
                bubble &&
                root.contains(bubble) &&
                !e.target.closest("a, button, textarea") &&
                !window.getSelection()?.toString()
            ) {
                const owner = bubble.closest(".chat-msg");
                if (owner.querySelector(".chat-msg__menu")) {
                    if (owner.classList.contains("is-menu-open")) closeMenus();
                    else openMenu(owner);
                }
            }
            return;
        }
        closeMenus();
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

    // Esc steps back one layer: open menu, then an open edit, then a reply in progress; only
    // when none is open does it fall through (the widget closes itself on Esc). Capture phase,
    // so the widget's own Esc handler never sees an Esc that was already used.
    root.addEventListener(
        "keydown",
        (e) => {
            if (e.key !== "Escape") return;
            if (quickMenu && !quickMenu.hidden) {
                setQuick(false);
                quickToggle?.focus();
                e.stopPropagation();
                return;
            }
            const open = root.querySelector(".chat-msg.is-menu-open");
            if (open) {
                const toggle = open.querySelector("[data-chat-menu-toggle]");
                closeMenus();
                toggle?.focus();
                e.stopPropagation();
                return;
            }
            if (editing && e.target === input) {
                endEdit();
                e.stopPropagation();
                return;
            }
            if (reply && e.target === input) {
                clearReply();
                e.stopPropagation();
            }
        },
        true,
    );

    // A reply quote is a button: Enter / Space jumps to the original message.
    root.addEventListener("keydown", (e) => {
        if (e.key !== "Enter" && e.key !== " ") return;
        const quote = e.target.closest?.(".chat-msg__quote[data-reply-id]");
        if (!quote || quote.classList.contains("is-gone")) return;
        e.preventDefault();
        jumpTo(
            log,
            log.querySelector(`[data-message-id="${quote.dataset.replyId}"]`),
        );
    });

    // Arrow keys move through an open menu or the quick-reply list; Tab leaves it and closes it.
    root.addEventListener("keydown", (e) => {
        const menu = e.target.closest?.(
            ".chat-msg__menu, [data-chat-quick-menu]",
        );
        if (!menu) return;
        if (e.key === "Tab") {
            closeMenus();
            setQuick(false);
            return;
        }
        if (e.key !== "ArrowDown" && e.key !== "ArrowUp") return;
        e.preventDefault();
        const items = [...menu.querySelectorAll("button:not(:disabled)")];
        const i = items.indexOf(document.activeElement);
        const step = e.key === "ArrowDown" ? 1 : -1;
        items[(i + step + items.length) % items.length]?.focus();
    });

    // ---- send ------------------------------------------------------------------

    // Optimistic send: the bubble appears at once as "Sending…". If the request fails it stays in
    // place as "Failed. Tap to retry" (plus Discard), keeping its text, files and reply target, so
    // nothing is lost and the composer is free for the next message.
    const sendingLabel = (entry) => {
        const { msg, time, meta } = entry;
        meta.querySelectorAll(".chat-msg__retry, .chat-msg__discard").forEach(
            (n) => n.remove(),
        );
        msg.classList.remove("is-failed");
        msg.classList.add("is-sending");
        msg.setAttribute("aria-busy", "true");
        time.textContent = "Sending…";
    };

    const failedLabel = (entry) => {
        const { msg, time, meta } = entry;
        msg.classList.remove("is-sending");
        msg.classList.add("is-failed");
        msg.removeAttribute("aria-busy");
        const retry = el("button", "chat-msg__retry", "Failed. Tap to retry");
        retry.type = "button";
        retry.addEventListener("click", () => deliver(entry));
        const discard = el("button", "chat-msg__discard", "Discard");
        discard.type = "button";
        discard.setAttribute(
            "aria-label",
            "Discard message that failed to send",
        );
        discard.addEventListener("click", () => {
            msg.remove();
            tidyDays(log);
            input.focus();
        });
        meta.prepend(retry, discard);
        // Focus stays where the sender is typing; the error line (role="alert") announces it.
    };

    async function deliver(entry) {
        const { msg, time, bubble, body, sent, pendingReply } = entry;
        if (msg.classList.contains("is-sending")) return;
        sendingLabel(entry);
        setError("");

        // FormData is rebuilt on every attempt: a retry sends exactly what the first try did.
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
            msg.classList.remove("is-sending");
            msg.removeAttribute("aria-busy");
            time.textContent = timeLabel();
            time.setAttribute("datetime", now.toISOString());
            time.title = formatStamp(now);
            if (json?.message?.id) {
                msg.dataset.messageId = json.message.id;
                if (json.message.edit_expires_at) {
                    msg.dataset.editableUntil = json.message.edit_expires_at;
                }
                bubble.append(messageActions(root, json.message.id, true));
            }
            root.dispatchEvent(
                new CustomEvent("chat:sent", {
                    bubbles: true,
                    detail: { body, files: sent.length },
                }),
            );
        } catch (err) {
            failedLabel(entry);
            setError(
                err.status === 422 && err.message
                    ? err.message
                    : "Message not sent. Check your connection, then tap the message to retry.",
            );
        }
    }

    form.addEventListener("submit", (e) => {
        e.preventDefault();
        if (editing) {
            saveEdit();
            return;
        }
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

        const entry = {
            ...appendMessage(
                log,
                body,
                sent,
                pendingReply,
                root.dataset.chatOther || "them",
            ),
            body,
            sent,
            pendingReply,
        };
        deliver(entry);
    });
}
