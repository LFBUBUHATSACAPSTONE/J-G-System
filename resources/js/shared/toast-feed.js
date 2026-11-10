// Toasts between the two parties (docs/toast-notifications-phases.md, Phase 4A, 4B and 4C).
//
// Cross-page toasts read the existing `admin.messages.updates` and `user.messages.updates` feeds. Messages
// remain in the chat feed; booking workflow events are read from persisted database notifications. Approved
// bookings retain their existing booking-card feed, which also updates the chat timeline.
//
// Where the settings come from: <x-toast.region feed="admin|user" /> prints `data-toast-feed` (URL, link, texts,
// pace). No attribute (guest, Admin Messages page) = this file does nothing.
//
// Two modes, one request per page at most:
//   - LISTEN: the page already polls (the landing chat widget, chat.js `initChatStream`). chat.js announces each
//     answer with a `chat:polled` event and this file reads it. No request of its own.
//   - OWN POLL: every other page (admin pages, account pages, booking flow, terms). Slow (poll_ms), paused while
//     the tab is hidden, doubling after a failure up to max_ms, stopped for good on 401 / 403 / 419.
//   - Dedicated Messages panels show the thread live: record incoming message cursors silently, without a toast.
//
// Baseline (a page that has just loaded must not toast the past): the first poll of a browser session is silent.
// The response supplies the latest relevant message id as a cursor. Later polls request only newer messages,
// regardless of whether the unread total changed (it may change concurrently when another view marks messages
// read). The state lives in sessionStorage (`jg.toast.feed.<side>`), so moving between pages neither replays nor
// loses a message. If storage is blocked the feed still works, it just starts from a new baseline on every page.
//
// "Booking approved" (client side): the `bookings` list holds this client's newly approved bookings. Other
// workflow notifications carry their own database notification id and are de-duplicated separately.
//
// A burst is one toast ("3 new replies from J&G", "3 of your bookings were approved"), never three.

import { showToast } from "./toast.js";

const UNREACHABLE_ID = 2147483647; // after this id nothing exists: a poll costs one count query
const STOP_STATUSES = [401, 403, 419];

function start() {
    const region = document.querySelector("[data-toast-region]");
    let cfg = null;
    try {
        cfg = JSON.parse(region?.dataset.toastFeed || "null");
    } catch {
        cfg = null;
    }
    if (!cfg?.url) return;

    const text = cfg.text || {};
    const key = `jg.toast.feed.${cfg.side}`;
    let state = {
        after: null,
        unread: null,
        base: null,
        since: null,
        seen: [],
        workflowSeen: [],
    };

    try {
        const saved = JSON.parse(sessionStorage.getItem(key) || "null");
        if (saved && typeof saved === "object") state = { ...state, ...saved };
        if (!Array.isArray(state.seen)) state.seen = [];
        if (!Array.isArray(state.workflowSeen)) state.workflowSeen = [];
    } catch {
        // storage unavailable: a fresh baseline on this page
    }
    const save = () => {
        try {
            sessionStorage.setItem(key, JSON.stringify(state));
        } catch {
            // storage unavailable
        }
    };

    // A booking of this person changed (approved, declined, cancelled, a cancellation answered ...). Pages that show
    // booking cards listen for this and re-read them (resources/js/account/bookings.js), so a card never keeps an old
    // state after J&G has answered. Not sent for the quiet baseline poll: a page that has just loaded is already current.
    const notifyBookingUpdate = () =>
        document.dispatchEvent(new CustomEvent("toast-feed:booking-update"));

    const widget = document.querySelector(
        "[data-chat-widget][data-chat-updates-url]",
    );
    const adminMessages =
        cfg.side === "admin"
            ? document.querySelector("[data-messages][data-chat-updates-url]")
            : null;
    const accountMessages =
        cfg.side === "user"
            ? document.querySelector(
                  "[data-account-chat][data-chat-updates-url]",
              )
            : null;

    const rememberCursor = (top) => {
        if (top > (state.after ?? 0)) {
            state.after = top;
            save();
        }
    };

    const rememberMessages = (messages) =>
        rememberCursor(
            Math.max(0, ...messages.map((message) => Number(message?.id) || 0)),
        );

    const rememberRenderedMessages = () => {
        const top = [...document.querySelectorAll("[data-message-id]")].reduce(
            (max, node) => Math.max(max, Number(node.dataset.messageId) || 0),
            0,
        );
        rememberCursor(top);
    };

    if (adminMessages || accountMessages) rememberRenderedMessages();

    // The text for n new messages, and where the toast leads.
    const announce = (messages, { open = null } = {}) => {
        const list = messages.filter((m) => m && !m.deleted);
        const ids = messages.map((m) => Number(m?.id) || 0);
        const top = Math.max(0, ...ids);
        if (top > (state.after ?? 0)) {
            state.after = top;
            save();
        }
        if (!list.length) return;
        const message =
            list.length === 1
                ? text.message
                : String(text.messages || "").replace(":count", list.length);
        const action = open
            ? { label: text.open, onClick: open }
            : cfg.link
              ? { label: text.open, href: cfg.link }
              : null;
        showToast({ tone: "info", message, action, icon: "chat" });
    };

    // Bookings approved for this client (4C). Remembers every id it is given, so the same booking is never
    // toasted twice. `quiet` = remember only (the person is looking at the chat that shows the card).
    const announceBookings = (bookings, { quiet = false } = {}) => {
        if (cfg.side !== "user") return;
        const fresh = bookings.filter(
            (b) => b && b.id && !state.seen.includes(b.id),
        );
        if (!fresh.length) return;
        state.seen = [...state.seen, ...fresh.map((b) => b.id)].slice(-50);
        save();
        if (quiet) return;
        notifyBookingUpdate();
        const message =
            fresh.length === 1
                ? String(text.booking_approved || "").replace(
                      ":reference",
                      fresh[0].reference ? `#${fresh[0].reference}` : "",
                  )
                : String(text.bookings_approved || "").replace(
                      ":count",
                      fresh.length,
                  );
        showToast({
            tone: "success",
            icon: "booking-approved",
            message: message.replace(/\s{2,}/g, " ").trim(),
            action: cfg.bookingsLink
                ? { label: text.open_bookings, href: cfg.bookingsLink }
                : null,
        });
    };

    const announceWorkflowNotifications = (
        notifications,
        { quiet = false } = {},
    ) => {
        const fresh = notifications.filter(
            (notification) =>
                notification?.id &&
                !state.workflowSeen.includes(String(notification.id)),
        );
        if (!fresh.length) return;

        state.workflowSeen = [
            ...state.workflowSeen,
            ...fresh.map((notification) => String(notification.id)),
        ].slice(-100);
        save();
        if (quiet) return;
        notifyBookingUpdate();

        const templates = text.workflow || {};
        fresh.forEach((notification) => {
            let message = templates[notification.event];
            if (!message) return;

            const reference = notification.reference
                ? `#${notification.reference}`
                : "";
            message = message
                .replaceAll(":reference", reference)
                .replaceAll(":client", notification.client_name || "a client");

            const href =
                cfg.side === "admin" && cfg.bookingsLink
                    ? `${cfg.bookingsLink}?booking=${encodeURIComponent(notification.booking_id)}`
                    : cfg.bookingsLink || cfg.link;
            const action = href
                ? { label: text.workflow_open || text.open, href }
                : null;
            const tone =
                notification.event === "booking_declined" ||
                notification.event === "booking_cancelled"
                    ? "warning"
                    : "info";
            const icon =
                notification.event === "booking_cancelled" ||
                notification.event === "cancellation_approved"
                    ? "booking-cancelled"
                    : "booking-event";

            showToast({ tone, message, action, icon });
        });
    };

    // ---- LISTEN: the landing chat widget / admin Messages already polls ------------------------
    if (widget || adminMessages || accountMessages) {
        document.addEventListener("chat:polled", (event) => {
            const detail = event.detail || {};
            if (widget && !detail.widget) return;
            const firstWorkflowPoll = state.since === null;
            announceWorkflowNotifications(detail.workflowNotifications || [], {
                quiet: firstWorkflowPoll,
            });
            const syncedAt =
                detail.notificationsSyncedAt || detail.bookingsSyncedAt;
            if (syncedAt) {
                state.since = syncedAt;
                save();
            }

            // Dedicated Messages panels display incoming messages inline. Keep their cursor in
            // sync so those messages are not toasted later after navigating to another view.
            if ((adminMessages || accountMessages) && !detail.widget) {
                rememberMessages(detail.messages || []);
                return;
            }

            // The thread is open and the person is looking at it: it shows there, no toast.
            const watching =
                detail.panelOpen && document.hasFocus() && !document.hidden;
            const open = () => {
                const panel = widget.querySelector("[data-chat-panel]");
                if (panel?.hidden)
                    widget.querySelector("[data-chat-toggle]")?.click();
            };

            const bookings = detail.bookings || [];
            if (bookings.length) {
                if (detail.bookingsSyncedAt)
                    state.since = detail.bookingsSyncedAt;
                announceBookings(bookings, { quiet: watching });
            }

            const messages = (detail.messages || []).filter(
                (m) => m && m.from !== "client",
            );
            if (!messages.length) return;
            if (watching) {
                const top = Math.max(
                    0,
                    ...messages.map((m) => Number(m.id) || 0),
                );
                if (top > (state.after ?? 0)) {
                    state.after = top;
                    save();
                }
                return;
            }
            announce(messages, { open });
        });
        return;
    }

    // ---- OWN POLL ----------------------------------------------------------------------------
    const wanted = cfg.side === "admin" ? "client" : "admin";
    let busy = false;
    let stopped = false;
    let failures = 0;
    let timer = 0;

    const get = async (after) => {
        const now = encodeURIComponent(new Date().toISOString());
        // `since` is the booking cursor: the stored server time once known, else now (an empty booking list).
        // changed_since = now: the edit/unsend list of the same answer comes back empty.
        const since = encodeURIComponent(
            state.since || new Date().toISOString(),
        );
        const res = await fetch(
            `${cfg.url}?after=${after}&since=${since}&changed_since=${now}`,
            {
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            },
        );
        if (STOP_STATUSES.includes(res.status)) {
            stopped = true;
            return null;
        }
        if (!res.ok) throw new Error(String(res.status));
        return res.json();
    };

    const handle = async (data) => {
        const unread = Number(data.unread) || 0;
        const firstWorkflowPoll = state.since === null;
        announceWorkflowNotifications(data.workflow_notifications || [], {
            quiet: firstWorkflowPoll,
        });
        if (data.notifications_synced_at) {
            state.since = data.notifications_synced_at;
            save();
        }

        // Bookings approved since the cursor (client side). The very first poll of a session is silent.
        if (cfg.side === "user") {
            if (!firstWorkflowPoll) announceBookings(data.bookings || []);
            if (data.bookings_synced_at) {
                state.since = data.bookings_synced_at;
                save();
            }
        }

        // Cursor known: the answer holds only messages newer than it.
        if (state.after !== null) {
            announce((data.messages || []).filter((m) => m.from === wanted));
            state.unread = unread;
            save();
            return;
        }

        // First poll of the session: stay silent for existing messages and learn the id baseline.
        if (state.base === null) {
            state.base = data.messages_synced_at || new Date().toISOString();
            state.after = Number(data.message_cursor) || 0;
            state.unread = unread;
            save();
            return;
        }

        // Migrate a feed state saved by the earlier unread-count-based cursor. Fetch once and
        // use its timestamp baseline so a message arriving during the upgrade is not lost.
        const full = await get(0);
        if (!full) return;
        const since = Date.parse(state.base) || 0;
        const fresh = (full.messages || []).filter(
            (m) => m.from === wanted && Date.parse(m.at) >= since,
        );
        const top = Math.max(
            0,
            ...(full.messages || []).map((m) => Number(m.id) || 0),
        );
        state.after = top;
        state.unread = unread;
        save();
        announce(fresh);
    };

    const schedule = () => {
        window.clearTimeout(timer);
        if (stopped || document.hidden) return; // resumes on visibilitychange
        const base = Number(cfg.pollMs) || 15000;
        const cap = Number(cfg.maxMs) || 60000;
        timer = window.setTimeout(poll, Math.min(base * 2 ** failures, cap));
    };

    const poll = async () => {
        if (busy || stopped || document.hidden) return;
        busy = true;
        window.clearTimeout(timer);
        try {
            const data = await get(state.after ?? UNREACHABLE_ID);
            if (data) {
                failures = 0;
                await handle(data);
            }
        } catch {
            failures = Math.min(failures + 1, 4); // backs off, shows nothing
        } finally {
            busy = false;
            schedule();
        }
    };

    // Back in the tab: catch up once, then the slow pace again.
    document.addEventListener("visibilitychange", () => {
        if (document.hidden) window.clearTimeout(timer);
        else poll();
    });

    poll();
}

if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", start);
else start();
