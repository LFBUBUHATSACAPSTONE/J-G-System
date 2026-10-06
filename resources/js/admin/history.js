// Admin Booking History page: state tabs, filters, sort and search over the rows already on the
// page.
// The phone filter sheet (chips, count badge, "Clear all") is started by bookings.js for every
// [data-filter-bar], so this file only has to announce each apply() (see the end of apply()).
// Row markup comes from <x-admin.booking-row :history="true">: each <tr> carries
//   data-status-group (ongoing | upcoming | completed | cancelled), data-rank, data-date (start),
//   data-month (Y-m of the start date), data-package, data-name, data-search.

function init() {
    const body = document.querySelector("[data-history-body]");
    if (!body) return;

    const rows = [...body.querySelectorAll("[data-booking-row]")];
    const tabs = [...document.querySelectorAll("[data-history-tab]")];
    const sortSelect = document.querySelector("[data-history-sort]");
    const packageSelect = document.querySelector("[data-history-package]");
    const monthSelect = document.querySelector("[data-history-month]");
    const searchInput = document.querySelector("[data-history-search]");
    const emptyMessage = document.querySelector("[data-history-empty]");
    const count = document.querySelector("[data-history-count]");

    if (!rows.length) return;

    let phase = "all";

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
        const month = monthSelect.value;
        const query = searchInput.value.trim().toLowerCase();

        const visible = new Set(
            rows.filter(
                (row) =>
                    (phase === "all" || row.dataset.statusGroup === phase) &&
                    (pkg === "all" || row.dataset.package === pkg) &&
                    (month === "all" || row.dataset.month === month) &&
                    (!query || row.dataset.search.includes(query)),
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
    [sortSelect, packageSelect, monthSelect].forEach((el) =>
        el.addEventListener("change", apply),
    );
    searchInput.addEventListener("input", apply);

    apply(); // sets the Default order on load
}

if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", init);
else init();
