// Admin Booking History page: state tabs, filters, event date range, sort and search over the rows
// already on the page. The date range (From / To / chips) is shared with Bookings: ./date-range.js.
// It is kept in ?from=YYYY-MM-DD&to=YYYY-MM-DD; invalid dates are ignored.
// The phone filter sheet (chips, count badge, "Clear all") is started by bookings.js for every
// [data-filter-bar], so this file only has to announce each apply() (see the end of apply()).
// Row markup comes from <x-admin.booking-row :history="true">: each <tr> carries
//   data-status-group (ongoing | upcoming | completed | cancelled), data-rank, data-date (start),
//   data-end-date, data-package, data-name, data-search.

import {
    initDateRange,
    overlapsRange,
    isIsoDate,
    dateMessage,
} from "./date-range.js";

function init() {
    const body = document.querySelector("[data-history-body]");
    if (!body) return;

    const rows = [...body.querySelectorAll("[data-booking-row]")];
    const tabs = [...document.querySelectorAll("[data-history-tab]")];
    const sortSelect = document.querySelector("[data-history-sort]");
    const packageSelect = document.querySelector("[data-history-package]");
    const searchInput = document.querySelector("[data-history-search]");
    const emptyMessage = document.querySelector("[data-history-empty]");
    const count = document.querySelector("[data-history-count]");

    if (!rows.length) return;

    let phase = "all";

    // Event date range. Rows carry data-date (start) and data-end-date; a booking matches when its
    // dates overlap the range. Works together with the tabs, Package and Search (all must match).
    const dateRoot = document.querySelector("[data-date-range]");
    const dates = dateRoot
        ? initDateRange(dateRoot, {
              onChange: () => {
                  syncDateParams();
                  apply();
              },
          })
        : null;
    const emptyText = document.querySelector("[data-history-empty-text]");
    const emptyClear = document.querySelector("[data-history-empty-clear]");
    const emptyDefault = emptyText?.textContent.trim() ?? "";
    const dateLabels = JSON.parse(emptyMessage?.dataset.dateMessages || "{}");

    function syncDateParams() {
        const { from, to } = dates ? dates.get() : { from: "", to: "" };
        const params = new URLSearchParams(window.location.search);
        if (from) params.set("from", from);
        else params.delete("from");
        if (to) params.set("to", to);
        else params.delete("to");
        const query = params.toString();
        window.history.replaceState(
            null,
            "",
            window.location.pathname +
                (query ? `?${query}` : "") +
                window.location.hash,
        );
    }

    // Deep link / shared view: ?from= ?to=. From after To is swapped.
    if (dates) {
        const params = new URLSearchParams(window.location.search);
        let from = params.get("from") ?? "";
        let to = params.get("to") ?? "";
        if (!isIsoDate(from)) from = "";
        if (!isIsoDate(to)) to = "";
        if (from && to && from > to) [from, to] = [to, from];
        dates.set(from, to, { silent: true });
    }

    // Tab counts are totals per state, so they stay put while the other filters change.
    tabs.forEach((tab) => {
        const key = tab.dataset.historyTab;
        const total =
            key === "all"
                ? rows.length
                : rows.filter((row) => row.dataset.statusGroup === key).length;
        const badge = tab.querySelector("[data-history-tab-count]");
        if (badge) badge.textContent = total;
    });

    // Keys match config/admin/history.php 'sorts'.
    const byIndex = (a, b) => Number(a.dataset.index) - Number(b.dataset.index);
    const soonest = (a, b) => a.dataset.date.localeCompare(b.dataset.date);
    const latest = (a, b) => b.dataset.date.localeCompare(a.dataset.date);
    const comparators = {
        // Ongoing, then Upcoming (soonest first), then Completed and Cancelled (latest first).
        default: (a, b) =>
            Number(a.dataset.rank) - Number(b.dataset.rank) ||
            (a.dataset.statusGroup === "upcoming"
                ? soonest(a, b)
                : latest(a, b)) ||
            byIndex(a, b),
        alpha: (a, b) =>
            a.dataset.name.localeCompare(b.dataset.name, undefined, {
                sensitivity: "base",
            }) || byIndex(a, b),
        date: (a, b) => soonest(a, b) || byIndex(a, b),
        recent: (a, b) => latest(a, b) || byIndex(a, b),
    };

    function apply() {
        const pkg = packageSelect.value;
        const query = searchInput.value.trim().toLowerCase();

        const { from, to } = dates ? dates.get() : { from: "", to: "" };

        // Every filter except the dates, so the empty state can tell whether the dates are why
        // nothing is left.
        const matchesOthers = (row) =>
            (phase === "all" || row.dataset.statusGroup === phase) &&
            (pkg === "all" || row.dataset.package === pkg) &&
            (!query || row.dataset.search.includes(query));

        const visible = new Set(
            rows.filter(
                (row) =>
                    matchesOthers(row) &&
                    overlapsRange(
                        row.dataset.date,
                        row.dataset.endDate,
                        from,
                        to,
                    ),
            ),
        );

        rows.forEach((row) => {
            row.hidden = !visible.has(row);
        });

        // Re-append in the chosen order (moves the existing nodes, nothing is re-rendered).
        [...rows]
            .sort(comparators[sortSelect.value] ?? comparators.default)
            .forEach((row) => body.appendChild(row));

        tabs.forEach((tab) =>
            tab.setAttribute(
                "aria-pressed",
                String(tab.dataset.historyTab === phase),
            ),
        );

        emptyMessage.hidden = visible.size > 0;

        // Name the range when the dates are what emptied the list, with a way back.
        const datesCause =
            !visible.size && Boolean(from || to) && rows.some(matchesOthers);
        if (emptyText)
            emptyText.textContent = datesCause
                ? dateMessage(dateLabels, from, to)
                : emptyDefault;
        if (emptyClear) emptyClear.hidden = !datesCause;
        count.textContent = `Showing ${visible.size} of ${rows.length} bookings`;

        // Lets the filter bar (chips + badge) follow along, same event as the Bookings page.
        document.dispatchEvent(new CustomEvent("admin:filters-applied"));
    }

    tabs.forEach((tab) =>
        tab.addEventListener("click", () => {
            phase = tab.dataset.historyTab;
            apply();
        }),
    );
    [sortSelect, packageSelect].forEach((el) =>
        el.addEventListener("change", apply),
    );
    searchInput.addEventListener("input", apply);
    // "Clear dates" in the empty state (the one next to the inputs is wired in date-range.js).
    emptyClear?.addEventListener("click", () => dates?.clear());

    apply(); // sets the Default order on load
}

if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", init);
else init();
