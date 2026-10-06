// Admin filter bar (phones): shows the active Sort / Status / Package choices as removable chips,
// keeps the count badge on the "Filters" button in sync, and powers "Clear all".
// Generic on purpose: it reads the selects inside [data-filter-sheet]; a select counts as active when
// its chosen option is not the first one (the "All" / "Default" option). Filtering itself stays in
// bookings.js (or history.js), which listens to the selects' own `change` events.
// Markup hooks inside [data-filter-bar]: [data-filter-sheet], [data-filter-toggle], [data-filter-count],
// [data-filter-chips], [data-filter-clear].

export function initFilterSheet(bar) {
    const selects = [...bar.querySelectorAll("[data-filter-sheet] select")];
    const chips = bar.querySelector("[data-filter-chips]");
    const badge = bar.querySelector("[data-filter-count]");
    const toggle = bar.querySelector("[data-filter-toggle]");
    const sheetClear = bar.querySelectorAll("[data-filter-clear]");

    if (!selects.length || !chips) return;

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
        button.className = `admin-chip ${extraClass}`.trim();
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
        const count = active.length;

        chips.replaceChildren(
            ...active.map((select) => {
                const text = `${labelOf(select)}: ${valueOf(select)}`;
                return chip(text, `Remove filter ${text}`, () => {
                    reset(select);
                    // The chip is gone after the re-render, so keep keyboard focus somewhere useful.
                    toggle?.focus();
                });
            }),
        );

        if (count > 1) {
            chips.append(
                chip(
                    "Clear all",
                    "Clear all filters",
                    () => {
                        selects.forEach(reset);
                        toggle?.focus();
                    },
                    "admin-chip--clear",
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
        button.addEventListener("click", () => selects.forEach(reset)),
    );
    selects.forEach((select) => select.addEventListener("change", render));
    // bookings.js fires this after every apply(), including the ?status= / ?package= deep links.
    document.addEventListener("admin:filters-applied", render);

    render();
}
