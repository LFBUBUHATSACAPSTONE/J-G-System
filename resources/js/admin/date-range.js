// Shared by the admin Bookings and Booking History pages: the "Event date" From / To inputs, the
// Today / Tomorrow / This week chips and the date maths behind them.
//
// Markup hooks inside the root element ([data-date-range]):
//   [data-date-from] [data-date-to]    <input type="date">
//   [data-date-chip="today|tomorrow|this_week"]   quick ranges (aria-pressed while that range is set)
//   [data-date-clear]                  "Clear dates" (hidden while no range is set)
// Dates are plain YYYY-MM-DD strings, so they compare as text. "Today" is the date in Asia/Manila
// (the business's time), and the week runs Monday to Sunday. Labels come from config/admin/*.php.

const ZONE = "Asia/Manila";
const ISO = /^\d{4}-\d{2}-\d{2}$/;

export function isIsoDate(value) {
    if (!ISO.test(value ?? "")) return false;
    const [year, month, day] = value.split("-").map(Number);
    const date = new Date(Date.UTC(year, month - 1, day));
    return (
        date.getUTCFullYear() === year &&
        date.getUTCMonth() === month - 1 &&
        date.getUTCDate() === day
    );
}

export function manilaToday(now = new Date()) {
    // en-CA formats as YYYY-MM-DD.
    return new Intl.DateTimeFormat("en-CA", {
        timeZone: ZONE,
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    }).format(now);
}

export function addDays(iso, days) {
    const [year, month, day] = iso.split("-").map(Number);
    return new Date(Date.UTC(year, month - 1, day + days))
        .toISOString()
        .slice(0, 10);
}

export function weekRange(iso) {
    const [year, month, day] = iso.split("-").map(Number);
    const weekday = new Date(Date.UTC(year, month - 1, day)).getUTCDay(); // 0 = Sunday
    const from = addDays(iso, -((weekday + 6) % 7)); // back to Monday
    return { from, to: addDays(from, 6) };
}

export function chipRange(key, today = manilaToday()) {
    if (key === "today") return { from: today, to: today };
    if (key === "tomorrow") {
        const tomorrow = addDays(today, 1);
        return { from: tomorrow, to: tomorrow };
    }
    if (key === "this_week") return weekRange(today);
    return null;
}

// A booking matches when its event dates (start to end) overlap the range, so a multi-day event
// shows on every day it covers. A missing end of the range means "onward" or "up to".
export function overlapsRange(start, end, from, to) {
    const last = end || start;
    return (!from || last >= from) && (!to || start <= to);
}

export function formatDate(iso) {
    const [year, month, day] = iso.split("-").map(Number);
    return new Intl.DateTimeFormat("en-US", {
        timeZone: "UTC",
        month: "short",
        day: "numeric",
        year: "numeric",
    }).format(new Date(Date.UTC(year, month - 1, day)));
}

// Short date for chips: "Nov 10", with the year only when it is not the current year (Manila).
function formatChipDate(iso) {
    const [year, month, day] = iso.split("-").map(Number);
    const sameYear = year === Number(manilaToday().slice(0, 4));
    return new Intl.DateTimeFormat("en-US", {
        timeZone: "UTC",
        month: "short",
        day: "numeric",
        ...(sameYear ? {} : { year: "numeric" }),
    }).format(new Date(Date.UTC(year, month - 1, day)));
}

// Text for the active-range chip in the phone filter sheet, from the config 'summary' templates.
export function dateSummary(labels, from, to) {
    const template =
        from && to
            ? from === to
                ? labels.single
                : labels.range
            : from
              ? labels.from
              : labels.to;
    return (template ?? "")
        .replace(":from", from ? formatChipDate(from) : "")
        .replace(":to", to ? formatChipDate(to) : "");
}

// Empty-state text from the config templates ("No bookings from :from to :to", ...).
export function dateMessage(labels, from, to) {
    const template =
        from && to
            ? from === to
                ? labels.empty_single
                : labels.empty
            : from
              ? labels.empty_from
              : labels.empty_to;
    return (template ?? "")
        .replace(":from", from ? formatDate(from) : "")
        .replace(":to", to ? formatDate(to) : "");
}

// Wires the inputs, chips and clear button. `onChange({ from, to })` runs after every change the
// admin makes (not after set/clear with { silent: true }, used for deep links).
export function initDateRange(root, { onChange } = {}) {
    const fromInput = root.querySelector("[data-date-from]");
    const toInput = root.querySelector("[data-date-to]");
    const chips = [...root.querySelectorAll("[data-date-chip]")];
    const clearButtons = [...root.querySelectorAll("[data-date-clear]")];
    if (!fromInput || !toInput) return null;

    // An unfinished or invalid date reads as empty.
    const read = () => ({
        from: isIsoDate(fromInput.value) ? fromInput.value : "",
        to: isIsoDate(toInput.value) ? toInput.value : "",
    });

    function render() {
        const { from, to } = read();
        const today = manilaToday();
        chips.forEach((chip) => {
            const range = chipRange(chip.dataset.dateChip, today);
            const pressed =
                Boolean(range) && range.from === from && range.to === to;
            chip.setAttribute("aria-pressed", String(pressed));
        });
        clearButtons.forEach((button) => (button.hidden = !(from || to)));
        toInput.min = from;
    }

    function changed() {
        render();
        onChange?.(read());
    }

    // One day is one pick: choosing From fills To with the same day. Clearing To afterwards
    // leaves "from this day onward".
    fromInput.addEventListener("change", () => {
        const { from, to } = read();
        if (from && (!to || to < from)) toInput.value = from;
        changed();
    });

    toInput.addEventListener("change", () => {
        const { from, to } = read();
        if (to && from && to < from) fromInput.value = to;
        changed();
    });

    chips.forEach((chip) =>
        chip.addEventListener("click", () => {
            // Clicking the active chip turns it off.
            if (chip.getAttribute("aria-pressed") === "true")
                return api.clear();
            const range = chipRange(chip.dataset.dateChip);
            if (range) api.set(range.from, range.to);
        }),
    );

    clearButtons.forEach((button) =>
        button.addEventListener("click", () => {
            api.clear();
            fromInput.focus();
        }),
    );

    const api = {
        get: read,
        isActive: () => {
            const { from, to } = read();
            return Boolean(from || to);
        },
        set(from, to, { silent = false } = {}) {
            fromInput.value = isIsoDate(from) ? from : "";
            toInput.value = isIsoDate(to) ? to : "";
            if (silent) render();
            else changed();
        },
        clear({ silent = false } = {}) {
            api.set("", "", { silent });
        },
    };

    render();
    return api;
}
