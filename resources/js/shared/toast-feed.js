// Toasts between the two parties (docs/toast-notifications-phases.md, Phase 4A, 4B and 4C).
//
// A client message shows as a toast to the admin, an admin reply shows as a toast to the client, on pages that
// do not already show the thread. It reads the endpoints that already exist (`admin.messages.updates`,
// `user.messages.updates`); nothing new on the server. Fed so far: messages both ways (4B) and, for the client,
// "your booking was approved" (4C). The other booking events (declined, payment verified, cancellation decisions,
// admin edits, and everything the admin should hear about) wait for the backend decision recorded in
// docs/backend-impact.md: the `bookings` list of the `updates` answers only carries APPROVED bookings.
//
// Where the settings come from: <x-toast.region feed="admin|user" /> prints `data-toast-feed` (URL, link, texts,
// pace). No attribute (guest, Admin Messages page) = this file does nothing.
//
// Two modes, one request per page at most:
//   - LISTEN: the page already polls (the landing chat widget, chat.js `initChatStream`). chat.js announces each
//     answer with a `chat:polled` event and this file reads it. No request of its own.
//   - OWN POLL: every other page (admin pages, account pages, booking flow, terms). Slow (poll_ms), paused while
//     the tab is hidden, doubling after a failure up to max_ms, stopped for good on 401 / 403 / 419.
//   - Account > Messages already shows the thread live: nothing to do there.
//
// Baseline (a page that has just loaded must not toast the past): the first poll of a browser session is silent.
// It asks for messages after an id nothing can reach, so only `unread` and the server clock come back. When
// `unread` later goes UP, the list is fetched once, only messages created after the baseline are toasted, and the
// highest id is kept as the cursor. From then on each poll asks only for newer messages. The state lives in
// sessionStorage (`jg.toast.feed.<side>`), so moving between pages neither replays nor loses a message. If storage
// is blocked the feed still works, it just starts from a new baseline on every page.
//
// "Booking approved" (client side only): the `bookings` list of the same `updates` answer holds this client's
// bookings approved after the `since` cursor. The first poll of a session is silent for it too; after that the
// server's `bookings_synced_at` is kept as the cursor, and the ids already toasted are remembered (`seen`) so a
// booking is never toasted twice, even when the landing-page widget and an own-poll page both saw it. The admin side
// gets nothing from this list: it only holds approvals the admin made.
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

    // Account > Messages shows the thread live.
    if (document.querySelector("[data-account-chat]")) return;

    const text = cfg.text || {};
    const key = `jg.toast.feed.${cfg.side}`;
    let state = {
        after: null,
        unread: null,
        base: null,
        since: null,
        seen: [],
    };

    try {
        const saved = JSON.parse(sessionStorage.getItem(key) || "null");
        if (saved && typeof saved === "object") state = { ...state, ...saved };
        if (!Array.isArray(state.seen)) state.seen = [];
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

    const widget = document.querySelector(
        "[data-chat-widget][data-chat-updates-url]",
    );

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
        showToast({ tone: "info", message, action });
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
            message: message.replace(/\s{2,}/g, " ").trim(),
            action: cfg.bookingsLink
                ? { label: text.open_bookings, href: cfg.bookingsLink }
                : null,
        });
    };

    // ---- LISTEN: the landing chat widget already polls ----------------------------------------
    if (widget) {
        document.addEventListener("chat:polled", (event) => {
            const detail = event.detail || {};
            if (!detail.widget) return;
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

        // Bookings approved since the cursor (client side). The very first poll of a session is silent.
        if (cfg.side === "user") {
            const firstTime = state.since === null;
            if (!firstTime) announceBookings(data.bookings || []);
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

        // First poll of the session: silent, learn the baseline.
        if (state.base === null) {
            state.base = data.messages_synced_at || new Date().toISOString();
            state.unread = unread;
            save();
            return;
        }

        // Something arrived since the baseline: fetch the list once.
        if (unread > (state.unread ?? 0)) {
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
            state.after = top; // from now on each poll asks only for newer ones
            state.unread = unread;
            save();
            announce(fresh);
            return;
        }

        state.unread = unread; // read elsewhere: keep the baseline in step
        save();
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
