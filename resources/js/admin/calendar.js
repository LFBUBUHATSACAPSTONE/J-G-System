// Admin Calendar page: hover a booked date to preview its bookings in the side panel, click (or
// tap, or press Enter) to pin it so the links inside stay reachable. Esc clears the pin.
// The panel content is server-rendered: each date has a <template data-cal-detail="YYYY-MM-DD">
// in the page, and this script only copies one of them into the panel.

function initCalendar(root) {
    const panel = root.querySelector("[data-cal-panel]");
    const title = root.querySelector("[data-cal-title]");
    const sub = root.querySelector("[data-cal-sub]");
    const body = root.querySelector("[data-cal-body]");
    const status = root.querySelector("[data-cal-status]");
    if (!panel || !title || !sub || !body) return;

    const buttons = [...root.querySelectorAll("button[data-cal-date]")];
    const details = new Map(
        [...root.querySelectorAll("template[data-cal-detail]")].map((t) => [
            t.dataset.calDetail,
            t,
        ]),
    );
    const stacked = window.matchMedia("(max-width: 1199.98px)"); // panel sits under the grid
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    let pinned = null;

    function render(date) {
        const template = date ? details.get(date) : null;
        body.replaceChildren();
        if (!template) {
            title.textContent = panel.dataset.emptyTitle;
            sub.textContent = panel.dataset.emptySub;
            sub.hidden = false;
            return;
        }
        title.textContent = template.dataset.title;
        sub.textContent = template.dataset.sub || ""; // the holiday name, if there is one
        sub.hidden = !template.dataset.sub;
        body.append(template.content.cloneNode(true));
    }

    function announce(message) {
        if (status) status.textContent = message;
    }

    function setPinned(date, { scroll = false } = {}) {
        pinned = date;
        buttons.forEach((b) =>
            b.setAttribute("aria-pressed", String(b.dataset.calDate === date)),
        );
        render(date);

        if (!date) {
            announce("Selection cleared.");
            return;
        }
        const count =
            details.get(date)?.content.querySelectorAll("article").length ?? 0;
        announce(
            `${title.textContent}: ${count} ${count === 1 ? "booking" : "bookings"} shown.`,
        );

        // On phones the panel is below the grid: bring it into view.
        if (scroll && stacked.matches) {
            panel.scrollIntoView({
                behavior: reduceMotion.matches ? "auto" : "smooth",
                block: "nearest",
            });
        }
    }

    buttons.forEach((button) => {
        const date = button.dataset.calDate;

        // Hover preview (mouse only: touch has no hover, and taps pin instead).
        button.addEventListener("pointerenter", (event) => {
            if (event.pointerType === "mouse") render(date);
        });
        button.addEventListener("pointerleave", (event) => {
            if (event.pointerType === "mouse") render(pinned);
        });

        button.addEventListener("click", () => {
            setPinned(pinned === date ? null : date, { scroll: true });
        });
    });

    root.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && pinned) setPinned(null);
    });
}

document.querySelectorAll("[data-calendar]").forEach(initCalendar);
