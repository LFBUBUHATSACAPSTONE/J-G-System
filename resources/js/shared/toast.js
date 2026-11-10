// Toast notifications (docs/toast-notifications.md). One entry point for every script:
//
//   import { showToast } from "../shared/toast.js";
//   showToast({ tone: "success", message: "Booking status updated." });
//   showToast({ tone: "info", message: "New message", action: { label: "Open", href: "/admin/messages" } });
//   showToast({ tone: "success", message: "Profile saved", icon: "profile" });   // icon key from config/toast.php 'icons'
//
// or, without importing anything:
//   document.dispatchEvent(new CustomEvent("toast:show", { detail: { tone: "danger", message: "..." } }));
//
// A result that is followed by a page reload: queueToast({ tone: "success", message }) (or the "toast:queue"
// event) first, then reload; the next page shows it once. See docs/toast-notifications-flash-audit.md.
//
// Scripts never build toast markup themselves. The empty container is <x-toast.region /> (it also carries
// the settings from config/toast.php in data-toast-config and the server flash as [data-toast-flash] markers).
//
// Behaviour: tones success | info | warning | danger. At most `max` are visible; the rest wait in a queue.
// The same tone + text within `mergeMs` is merged into one toast with a count. Success / info / warning
// close by themselves; danger stays until closed (duration 0). The timer pauses while the pointer or keyboard
// focus is on a toast and while the tab is hidden. Success / info / warning are role="status" (polite),
// danger is role="alert". Focus is never moved to a toast; Escape inside one closes it.
// Icon: each tone has a default icon; a toast may carry its own `icon` (a key of config/toast.php 'icons', or a
// Phosphor class matching ph-[a-z0-9-]+). Anything else falls back to the tone icon. Icons are classes, never HTML.
// The text is always set with textContent, never as HTML.

const TONES = {
    success: {
        icon: "ph-check-circle",
        live: "status",
        box: "border-success-border bg-success-soft text-success-text",
    },
    info: {
        icon: "ph-info",
        live: "status",
        box: "border-brand-text bg-brand-soft text-brand-text",
    },
    warning: {
        icon: "ph-warning",
        live: "status",
        box: "border-warning-border bg-warning-soft text-warning-text",
    },
    danger: {
        icon: "ph-warning-circle",
        live: "alert",
        box: "border-danger-border bg-danger-soft text-danger-text",
    },
};

// Tailwind class strings (kept whole so Tailwind can find them in this file).
const TOAST =
    "pointer-events-auto flex w-fit min-w-[18rem] max-w-full min-[576px]:max-w-[40rem] items-center gap-3 rounded-panel border px-4 py-3 text-fluid-sm shadow-[0_12px_32px_-8px_rgb(0_0_0/0.22),0_2px_6px_rgb(0_0_0/0.08)] animate-toast-in transition-[opacity,translate] duration-200 data-leaving:-translate-y-2 data-leaving:opacity-0 motion-reduce:animate-none motion-reduce:data-leaving:translate-y-0";
const ICON = "ph flex-none text-fluid-xl";
const TEXT = "m-0 min-w-0 flex-1 font-medium break-words";
const BADGE =
    "ml-2 inline-block rounded-full bg-black/10 px-2 text-xs font-semibold [[hidden]]:hidden";
const ACTION =
    "inline-flex flex-none cursor-pointer items-center gap-1 self-center rounded-full border-0 bg-transparent px-2 py-1 text-fluid-sm font-semibold whitespace-nowrap text-current no-underline hover:bg-black/10 focus-visible:bg-black/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current";
const CLOSE =
    "-mr-1 inline-flex size-8 flex-none cursor-pointer items-center justify-center rounded-full border-0 bg-transparent p-0 text-current hover:bg-black/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current";

const LEAVE_MS = 200;
const MIN_RESUME_MS = 1500; // a paused toast never closes right after it is released

let region = null;
let cfg = {
    max: 3,
    mergeMs: 5000,
    close: "Close notification",
    merged: "x:count",
    durations: { success: 5000, info: 5000, warning: 8000, danger: 0 },
    icons: {},
};
const active = [];
const queue = [];

function setup() {
    region = document.querySelector("[data-toast-region]");
    if (!region) return false;
    try {
        const given = JSON.parse(region.dataset.toastConfig || "{}");
        cfg = {
            ...cfg,
            ...given,
            durations: { ...cfg.durations, ...given.durations },
            icons: { ...cfg.icons, ...given.icons },
        };
    } catch {
        // keep the defaults
    }
    return true;
}

const ICON_PATTERN = /^ph-[a-z0-9-]+$/;

// A key of cfg.icons, or a Phosphor class that passes the pattern; otherwise the tone's own icon.
function iconFor(entry) {
    const given = entry.icon ? String(entry.icon) : "";
    const named =
        given && Object.hasOwn(cfg.icons, given) ? cfg.icons[given] : given;
    return ICON_PATTERN.test(named) ? named : TONES[entry.tone].icon;
}

function startTimer(entry) {
    if (!entry.duration || entry.timer || entry.hold || document.hidden) return;
    entry.startedAt = Date.now();
    entry.timer = window.setTimeout(() => dismiss(entry), entry.remaining);
}

function pauseTimer(entry) {
    if (!entry.timer) return;
    window.clearTimeout(entry.timer);
    entry.timer = 0;
    entry.remaining = Math.max(
        entry.remaining - (Date.now() - entry.startedAt),
        MIN_RESUME_MS,
    );
}

function renderCount(entry) {
    if (!entry.badge) return;
    entry.badge.hidden = entry.count < 2;
    entry.badge.textContent = cfg.merged.replace(":count", entry.count);
}

function build(entry) {
    const tone = TONES[entry.tone];
    const el = document.createElement("div");
    el.className = `${TOAST} ${tone.box}`;
    el.setAttribute("role", tone.live);
    el.dataset.toast = "";
    el.dataset.tone = entry.tone;

    const icon = document.createElement("i");
    icon.className = `${ICON} ${iconFor(entry)}`;
    icon.setAttribute("aria-hidden", "true");

    const text = document.createElement("p");
    text.className = TEXT;
    const message = document.createElement("span");
    message.textContent = entry.message;
    entry.badge = document.createElement("span");
    entry.badge.className = BADGE;
    entry.badge.hidden = true;
    text.append(message, entry.badge);

    el.append(icon, text);

    if (entry.action?.label) {
        const action = entry.action.href
            ? document.createElement("a")
            : document.createElement("button");
        action.className = ACTION;
        action.textContent = entry.action.label;
        const arrow = document.createElement("i");
        arrow.className = "ph ph-arrow-right text-fluid-base";
        arrow.setAttribute("aria-hidden", "true");
        action.append(arrow);
        if (entry.action.href) action.href = entry.action.href;
        else action.type = "button";
        action.addEventListener("click", () => {
            entry.action.onClick?.();
            dismiss(entry);
        });
        el.append(action);
    }

    const close = document.createElement("button");
    close.type = "button";
    close.className = CLOSE;
    close.setAttribute("aria-label", cfg.close);
    const x = document.createElement("i");
    x.className = "ph ph-x text-fluid-base";
    x.setAttribute("aria-hidden", "true");
    close.append(x);
    close.addEventListener("click", () => dismiss(entry));
    el.append(close);

    // Pause while the pointer or keyboard focus is on the toast.
    const hold = () => {
        entry.hold = entry.pointer || entry.focus;
        if (entry.hold) pauseTimer(entry);
        else startTimer(entry);
    };
    el.addEventListener("pointerenter", () => {
        entry.pointer = true;
        hold();
    });
    el.addEventListener("pointerleave", () => {
        entry.pointer = false;
        hold();
    });
    el.addEventListener("focusin", () => {
        entry.focus = true;
        hold();
    });
    el.addEventListener("focusout", (event) => {
        if (el.contains(event.relatedTarget)) return;
        entry.focus = false;
        hold();
    });
    el.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") return;
        event.stopPropagation();
        dismiss(entry);
    });

    renderCount(entry);
    return el;
}

function mount(entry) {
    entry.el = build(entry);
    region.append(entry.el);
    active.push(entry);
    startTimer(entry);
}

function dismiss(entry) {
    if (entry.leaving) return;
    entry.leaving = true;
    window.clearTimeout(entry.timer);
    entry.timer = 0;
    const index = active.indexOf(entry);
    if (index > -1) active.splice(index, 1);
    entry.el?.setAttribute("data-leaving", "");
    window.setTimeout(() => {
        entry.el?.remove();
        while (active.length < cfg.max && queue.length) mount(queue.shift());
    }, LEAVE_MS);
}

export function showToast({
    tone = "info",
    message = "",
    action = null,
    icon = "",
} = {}) {
    const text = String(message ?? "").trim();
    if (!text) return null;
    if (!region?.isConnected && !setup()) return null;
    const kind = TONES[tone] ? tone : "info";
    const now = Date.now();

    // The same toast again within the merge window: count it instead of stacking a copy.
    const same = [...active, ...queue].find(
        (entry) =>
            entry.tone === kind &&
            entry.message === text &&
            !entry.leaving &&
            now - entry.shownAt < cfg.mergeMs,
    );
    if (same) {
        same.count += 1;
        same.shownAt = now;
        if (same.el) {
            renderCount(same);
            pauseTimer(same);
            same.remaining = same.duration;
            startTimer(same);
        }
        return same;
    }

    const duration = Number(cfg.durations[kind] ?? 0);
    const entry = {
        tone: kind,
        message: text,
        action,
        icon,
        count: 1,
        duration,
        remaining: duration,
        shownAt: now,
        timer: 0,
        hold: false,
        pointer: false,
        focus: false,
        leaving: false,
    };
    if (active.length < cfg.max) mount(entry);
    else queue.push(entry);
    return entry;
}

// A result that must survive a page reload or a navigation (the page reloads right after a fetch() action,
// so a toast shown now would vanish). queueToast() saves the message in sessionStorage; the next page that has
// a <x-toast.region /> shows it once and removes it. Older than PENDING_MAX_AGE_MS = dropped. If storage is
// blocked the action still works, only the toast is skipped.
const PENDING_KEY = "jg.toast.pending";
const PENDING_MAX_AGE_MS = 60000;
const PENDING_MAX = 5;

export function queueToast({ tone = "success", message = "", icon = "" } = {}) {
    const text = String(message ?? "").trim();
    if (!text) return;
    try {
        const list = JSON.parse(sessionStorage.getItem(PENDING_KEY) || "[]");
        list.push({
            tone,
            message: text,
            icon: String(icon || ""),
            at: Date.now(),
        });
        sessionStorage.setItem(
            PENDING_KEY,
            JSON.stringify(list.slice(-PENDING_MAX)),
        );
    } catch {
        // storage unavailable: no toast, the action itself is unaffected
    }
}

// Shows what queueToast() saved before the last reload. Left in storage when this page has no region.
function showPending() {
    if (!region?.isConnected && !setup()) return;
    let list = [];
    try {
        list = JSON.parse(sessionStorage.getItem(PENDING_KEY) || "[]");
        sessionStorage.removeItem(PENDING_KEY);
    } catch {
        return;
    }
    if (!Array.isArray(list)) return;
    const now = Date.now();
    list.forEach((item) => {
        if (item && now - Number(item.at) <= PENDING_MAX_AGE_MS) {
            showToast({
                tone: item.tone,
                message: item.message,
                icon: item.icon,
            });
        }
    });
}

// Reads the server flash markers printed by <x-toast.flash /> and shows them once, then the queued ones.
function showFlash() {
    document.querySelectorAll("[data-toast-flash]").forEach((marker) => {
        showToast({
            tone: marker.dataset.tone,
            message: marker.dataset.message,
        });
        marker.remove();
    });
    showPending();
}

// A hidden tab pauses every timer; coming back resumes them.
document.addEventListener("visibilitychange", () => {
    active.forEach((entry) =>
        document.hidden ? pauseTimer(entry) : startTimer(entry),
    );
});

// For scripts that do not import this file.
document.addEventListener("toast:show", (event) => showToast(event.detail));
document.addEventListener("toast:queue", (event) => queueToast(event.detail));

if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", showFlash);
else showFlash();
