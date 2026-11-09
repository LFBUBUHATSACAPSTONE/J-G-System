// Admin filter bar (phones): shows the active Sort / Status / Package choices and the Event date
// range as removable chips, keeps the count badge on the "Filters" button in sync, and powers
// "Clear all". The date range counts as ONE active filter, however many ends it has.
// Generic on purpose: it reads the selects inside [data-filter-sheet]; a select counts as active when
// its chosen option is not the first one (the "All" / "Default" option). Filtering itself stays in
// bookings.js (or history.js), which listens to the selects' own `change` events.
// The range is read from the [data-date-range] group (inputs, see ./date-range.js) and cleared by
// clicking that group's own "Clear dates" button, so the page's filtering runs as usual.
// Markup hooks inside [data-filter-bar]: [data-filter-sheet], [data-filter-toggle], [data-filter-count],
// [data-filter-chips], [data-filter-clear].

import { dateSummary, isIsoDate } from "./date-range.js";

const CHIP =
    "admin-chip inline-flex min-h-9 cursor-pointer items-center gap-2 rounded-control border-0 px-3 py-1 text-[0.85rem] font-medium text-brand-text hover:bg-brand-soft-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text [&_.ph]:text-fluid-sm";
const CHIP_FILLED = "bg-brand-soft";
const CHIP_CLEAR = "admin-chip--clear bg-transparent underline"; // \"Clear all\": text only, no fill

export function initFilterSheet(bar) {
    const selects = [...bar.querySelectorAll("[data-filter-sheet] select")];
    const chips = bar.querySelector("[data-filter-chips]");
    const badge = bar.querySelector("[data-filter-count]");
    const toggle = bar.querySelector("[data-filter-toggle]");
    const sheetClear = bar.querySelectorAll("[data-filter-clear]");

    const dateRoot = bar.querySelector("[data-date-range]");
    const dateFrom = dateRoot?.querySelector("[data-date-from]");
    const dateTo = dateRoot?.querySelector("[data-date-to]");
    const dateClear = dateRoot?.querySelector("[data-date-clear]");
    const dateLabel = dateRoot?.getAttribute("aria-label") ?? "";
    const dateTemplates = JSON.parse(dateRoot?.dataset.dateSummary || "{}");

    if (!selects.length || !chips) return;

    // An unfinished or invalid date counts as empty, like in date-range.js.
    const dateRange = () => ({
        from: isIsoDate(dateFrom?.value) ? dateFrom.value : "",
        to: isIsoDate(dateTo?.value) ? dateTo.value : "",
    });

    // Goes through the group's own "Clear dates" button, which updates the page's filtering.
    const resetDates = () => {
        const { from, to } = dateRange();
        if (from || to) dateClear?.click();
    };

    const labelOf = (select) => {
        const label = select
            .closest(".admin-filter")
            ?.querySelector(".admin-filter__label");
        return (label?.textContent ?? "").replace(/:\s*$/, "").trim();
    };

    const valueOf = (select) =>
        select.options[select.selectedIndex]?.textContent.trim() ?? "";

    // Goes through the dropdown's patched `value` setter so the custom dropdown refreshes too.
    function reset(select) {
        if (select.selectedIndex === 0) return;
        select.value = select.options[0].value;
        select.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function chip(text, label, onClick, extraClass = "") {
        const button = document.createElement("button");
        button.type = "button";
        button.className = `${CHIP} ${extraClass ? CHIP_CLEAR : CHIP_FILLED}`;
        button.setAttribute("aria-label", label);
        const span = document.createElement("span");
        span.textContent = text;
        button.append(span);
        if (!extraClass) {
            const icon = document.createElement("i");
            icon.className = "ph ph-x";
            icon.setAttribute("aria-hidden", "true");
            button.append(icon);
        }
        button.addEventListener("click", onClick);
        return button;
    }

    function render() {
        const active = selects.filter((select) => select.selectedIndex > 0);
        const { from, to } = dateRange();
        const datesActive = Boolean(from || to);
        const count = active.length + (datesActive ? 1 : 0);

        const items = active.map((select) => {
            const text = `${labelOf(select)}: ${valueOf(select)}`;
            return chip(text, `Remove filter ${text}`, () => {
                reset(select);
                // The chip is gone after the re-render, so keep keyboard focus somewhere useful.
                toggle?.focus();
            });
        });

        if (datesActive) {
            const text = `${dateLabel}: ${dateSummary(dateTemplates, from, to)}`;
            items.push(
                chip(text, `Remove filter ${text}`, () => {
                    resetDates();
                    toggle?.focus();
                }),
            );
        }

        chips.replaceChildren(...items);

        if (count > 1) {
            chips.append(
                chip(
                    "Clear all",
                    "Clear all filters",
                    () => {
                        selects.forEach(reset);
                        resetDates();
                        toggle?.focus();
                    },
                    "clear",
                ),
            );
        }

        chips.hidden = count === 0;

        if (badge) {
            badge.textContent = count;
            badge.hidden = count === 0;
        }
        toggle?.setAttribute(
            "aria-label",
            count ? `Filters, ${count} active` : "Filters",
        );
        sheetClear.forEach((button) => (button.disabled = count === 0));
    }

    sheetClear.forEach((button) =>
        button.addEventListener("click", () => {
            selects.forEach(reset);
            resetDates();
        }),
    );
    selects.forEach((select) => select.addEventListener("change", render));
    // bookings.js fires this after every apply(), including the ?status= / ?package= deep links.
    document.addEventListener("admin:filters-applied", render);

    render();
}
