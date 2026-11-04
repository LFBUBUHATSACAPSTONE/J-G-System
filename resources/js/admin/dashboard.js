const dashboard = document.querySelector("[data-admin-dashboard]");

if (dashboard) {
    const config = JSON.parse(dashboard.dataset.config);
    const statsTarget = dashboard.querySelector("[data-dashboard-stats]");
    const attentionTarget = dashboard.querySelector(
        "[data-dashboard-attention]",
    );
    const attentionCount = dashboard.querySelector(
        "[data-dashboard-attention-count]",
    );
    const upcomingTarget = dashboard.querySelector("[data-dashboard-upcoming]");
    const packageTarget = dashboard.querySelector(
        "[data-dashboard-package-rate]",
    );
    const calendarLink = dashboard.querySelector("[data-dashboard-calendar]");

    const makeElement = (tag, className, text) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    };

    // Whole class strings (never built from pieces) so Tailwind can find them.
    const BADGE_BASE =
        "inline-flex items-center gap-2 rounded-full py-1 pr-4 pl-3 text-sm leading-[1.3] font-semibold whitespace-nowrap [&_.ph]:text-fluid-lg";
    const BADGE_TONES = {
        warning: "bg-warning-soft text-warning-text",
        success: "bg-success-soft text-success-text",
        danger: "bg-danger-soft text-danger-text",
        brand: "bg-brand-soft text-brand-text",
        neutral: "bg-panel text-ink",
    };
    const ALERT_DANGER =
        "mb-6 rounded-panel border border-danger-border bg-danger-soft px-6 py-3 font-medium text-danger-text";

    const EMPTY = "m-0 text-fluid-sm text-ink-muted";
    const LIST = "m-0 flex list-none flex-col gap-3 p-0";
    const MORE_BELOW =
        "inline-flex items-center gap-1 text-fluid-sm font-semibold text-brand-text no-underline focus-visible:rounded-field focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text mt-4";

    // KPI cards. Plain and featured differ only in colour.
    const STAT_CARD =
        "group/stat relative flex min-h-38 flex-col justify-between gap-3 rounded-card p-6 transition-shadow duration-150 ease-[ease] max-[575.98px]:min-h-0 max-[575.98px]:gap-2 max-[575.98px]:p-4 max-[399.98px]:grid max-[399.98px]:grid-cols-[minmax(0,1fr)_auto] max-[399.98px]:items-center max-[399.98px]:gap-x-3 hover:shadow-[0_0.25rem_1rem_oklch(0%_0_0/0.12)]";
    const STAT_LABEL =
        "m-0 text-fluid-label font-semibold max-[575.98px]:text-fluid-sm max-[399.98px]:col-start-1 max-[399.98px]:row-start-1";
    const STAT_LINK =
        "flex items-center justify-between gap-2 text-inherit no-underline after:absolute after:inset-0 after:rounded-[inherit] after:content-[''] focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:outline-offset-2 forced-colors:focus-visible:after:outline-[color:Highlight]";
    const STAT_GO =
        "flex-none text-fluid-heading transition-transform duration-150 ease-[ease] group-hover/stat:translate-x-[2px] group-hover/stat:-translate-y-[2px]";
    const STAT_BODY =
        "flex flex-wrap items-center gap-x-3 gap-y-2 max-[575.98px]:flex-nowrap max-[575.98px]:gap-2 max-[399.98px]:col-start-2 max-[399.98px]:row-span-2 max-[399.98px]:row-start-1";
    const STAT_VALUE =
        "m-0 text-fluid-stat font-bold leading-none max-[575.98px]:text-fluid-3xl";
    const STAT_DELTA =
        "inline-flex items-center gap-1 rounded-full px-3 py-1 text-[0.8rem] font-semibold max-[575.98px]:whitespace-nowrap";
    const STAT_CAPTION =
        "m-0 text-fluid-sm max-[575.98px]:text-[0.8rem] max-[575.98px]:whitespace-nowrap max-[399.98px]:col-start-1 max-[399.98px]:row-start-2";
    const STAT_PLAIN = {
        card: "bg-surface",
        label: "text-brand-text",
        value: "text-brand-text",
        caption: "text-ink-muted",
        ring: "focus-visible:after:outline-brand-text",
    };
    const STAT_FEATURED = {
        card: "bg-brand text-on-brand",
        label: "text-on-brand",
        value: "text-on-brand",
        caption: "text-on-brand-muted",
        ring: "focus-visible:after:outline-on-brand",
    };
    const DELTA_TONES = {
        good: "bg-success text-ink",
        bad: "bg-danger-soft text-danger-text",
        neutral: "bg-brand-soft text-brand-text",
    };

    // Needs your attention
    const ATTN_ITEM =
        "grid justify-items-start gap-x-4 gap-y-2 rounded-panel border-l-[0.3125rem] bg-brand-soft p-4 min-[576px]:grid-cols-[auto_minmax(0,1fr)_auto] min-[576px]:items-center min-[576px]:px-6";
    const ATTN_TONES = {
        warning: "border-l-warning-border",
        success: "border-l-success-border",
        danger: "border-l-danger-border",
    };
    const ATTN_TONE_DEFAULT = "border-l-brand-text";
    const ATTN_WHO =
        "m-0 text-fluid-base text-ink max-[575.98px]:flex max-[575.98px]:flex-col";
    const ATTN_REF = "ml-2 text-[0.85rem] text-ink-muted max-[575.98px]:ml-0";
    const ATTN_META = "mt-0.5 mb-0 text-[0.85rem] text-ink-muted";
    const ATTN_ACTION =
        "inline-flex items-center gap-1 rounded-full border border-brand-text bg-surface px-4 py-2 text-sm font-semibold whitespace-nowrap text-brand-text no-underline hover:bg-brand-soft-hover max-[575.98px]:min-h-11 max-[575.98px]:justify-center max-[575.98px]:justify-self-stretch focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text forced-colors:focus-visible:outline-[color:Highlight]";

    // Upcoming events
    const UP_ITEM =
        "grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-4 gap-y-1 rounded-panel bg-brand-soft p-3 text-ink no-underline hover:bg-brand-soft-hover hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text min-[576px]:grid-cols-[auto_minmax(0,1fr)_auto] min-[576px]:px-6 min-[576px]:py-4";
    const UP_DATE =
        "flex size-14 flex-col items-center justify-center rounded-field bg-brand leading-[1.1] text-on-brand";
    const UP_MONTH = "text-[0.7rem] font-semibold tracking-[0.05em] uppercase";
    const UP_DAY = "text-fluid-2xl font-bold";
    const UP_INFO = "flex min-w-0 flex-col";
    const UP_INFO_SUB = "text-[0.85rem] text-ink-muted";
    const UP_WHEN =
        "col-start-2 flex flex-col text-[0.85rem] min-[576px]:col-auto min-[576px]:items-end min-[576px]:text-right max-[575.98px]:flex-row max-[575.98px]:flex-wrap max-[575.98px]:gap-x-2";
    const UP_WHEN_STRONG =
        "font-semibold text-brand-text max-[575.98px]:whitespace-nowrap";
    const UP_WHEN_SPAN = "text-ink-muted max-[575.98px]:whitespace-nowrap";

    // Package Rate donut. Slice colours are in slice order (see --color-slice-* in tailwind.css).
    const SLICES = [
        "text-slice-1",
        "text-slice-2",
        "text-slice-3",
        "text-slice-4",
        "text-slice-5",
        "text-slice-6",
    ];
    const sliceClass = (item, index) =>
        item.other ? "text-ink-subtle" : SLICES[index % 6];
    const PR_BODY =
        "flex flex-wrap items-center gap-x-8 gap-y-6 max-[575.98px]:justify-center max-[575.98px]:gap-4";
    const PR_FIGURE = "relative size-48 flex-none max-[575.98px]:size-40";
    const PR_CHART = "block size-full";
    const PR_CENTER =
        "pointer-events-none absolute inset-0 flex flex-col items-center justify-center";
    const PR_TOTAL =
        "text-fluid-display leading-none font-bold text-brand-text";
    const PR_UNIT = "text-[0.8rem] text-ink-muted";
    const PR_LEGEND =
        "m-0 grid flex-[1_1_18rem] list-none grid-cols-[repeat(auto-fit,minmax(13rem,1fr))] gap-x-6 gap-y-4 p-0 max-[575.98px]:basis-full max-[575.98px]:grid-cols-[repeat(2,minmax(0,1fr))] max-[575.98px]:gap-3";
    const PR_ROW = "grid grid-cols-[auto_1fr] items-center gap-x-2";
    const PR_SWATCH = "row-span-2 size-5 rounded-full bg-current";
    const PR_NAME = "text-fluid-sm font-medium text-ink";
    const PR_NAME_LINK =
        "text-fluid-sm font-medium text-ink no-underline hover:text-brand-text hover:underline focus-visible:rounded-field focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text";
    const PR_MORE = "text-[0.8rem] font-normal text-ink-muted";
    const PR_PCT = "text-[0.8rem] text-ink-muted";

    const bookingsUrl = (params = {}) => {
        const url = new URL(config.bookingsUrl, window.location.href);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== "") {
                url.searchParams.set(key, value);
            }
        });
        return url.toString();
    };

    const dateAtLocalMidnight = (date) => new Date(`${date}T00:00:00`);
    const localDateString = (date) =>
        `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
    const dateLabel = (date, options) =>
        new Intl.DateTimeFormat(undefined, options).format(
            dateAtLocalMidnight(date),
        );
    const relativeTime = (timestamp) => {
        const difference = (new Date(timestamp).getTime() - Date.now()) / 1000;
        const units = [
            ["second", 1],
            ["minute", 60],
            ["hour", 60 * 60],
            ["day", 60 * 60 * 24],
        ];
        let divisor = 1;
        let unit = "second";
        for (const [nextUnit, threshold] of units) {
            if (Math.abs(difference) < threshold) continue;
            divisor = threshold;
            unit = nextUnit;
        }

        return new Intl.RelativeTimeFormat(undefined, {
            numeric: "auto",
        }).format(Math.round(difference / divisor), unit);
    };

    function renderStats(stats) {
        const fragment = document.createDocumentFragment();

        config.cards.forEach((card) => {
            const value = stats[card.key];
            if (!value) return;

            const palette = card.featured ? STAT_FEATURED : STAT_PLAIN;
            const article = makeElement(
                "article",
                `${STAT_CARD} ${palette.card}`,
            );
            const heading = makeElement("h2", `${STAT_LABEL} ${palette.label}`);
            const link = makeElement("a", `${STAT_LINK} ${palette.ring}`);
            link.href = bookingsUrl(
                card.status === "all" ? {} : { status: card.status },
            );
            link.append(
                document.createTextNode(card.label),
                makeElement(
                    "span",
                    "sr-only",
                    `: ${value.value}. View in Bookings`,
                ),
                makeElement("i", `ph ph-arrow-up-right ${STAT_GO}`),
            );
            link.lastElementChild.setAttribute("aria-hidden", "true");
            heading.append(link);
            article.append(heading);

            const body = makeElement("div", STAT_BODY);
            body.append(
                makeElement("p", `${STAT_VALUE} ${palette.value}`, value.value),
            );
            if (value.change !== null && value.change !== undefined) {
                const change = Number(value.change);
                const direction =
                    change > 0 ? "up" : change < 0 ? "down" : "flat";
                const tone =
                    direction !== "flat" && card.higher !== "neutral"
                        ? (direction === "up") === (card.higher === "good")
                            ? "good"
                            : "bad"
                        : "neutral";
                const icon =
                    direction === "flat"
                        ? "minus"
                        : direction === "up"
                          ? "arrow-up"
                          : "arrow-down";
                const delta = makeElement(
                    "span",
                    `${STAT_DELTA} ${DELTA_TONES[tone]}`,
                );
                const spoken =
                    direction === "flat"
                        ? "No change compared with last month"
                        : `${direction === "up" ? "Up" : "Down"} ${Math.abs(change)} compared with last month`;
                delta.append(
                    makeElement("i", `ph ph-${icon}`),
                    makeElement(
                        "span",
                        "",
                        direction === "flat"
                            ? "No change"
                            : `${change > 0 ? "+" : "−"}${Math.abs(change)}`,
                    ),
                    makeElement("span", "sr-only", spoken),
                );
                delta.firstElementChild.setAttribute("aria-hidden", "true");
                delta.children[1].setAttribute("aria-hidden", "true");
                body.append(delta);
            }
            article.append(body);
            article.append(
                makeElement(
                    "p",
                    `${STAT_CAPTION} ${palette.caption}`,
                    value.change === null || value.change === undefined
                        ? card.hint || ""
                        : "vs last month",
                ),
            );
            fragment.append(article);
        });

        statsTarget.replaceChildren(fragment);
    }

    function renderAttention(items) {
        const fragment = document.createDocumentFragment();
        const shown = items.slice(0, config.attentionLimit);

        attentionCount.hidden = items.length === 0;
        attentionCount.textContent = items.length;
        if (!shown.length) {
            fragment.append(
                makeElement(
                    "p",
                    EMPTY,
                    "You're all caught up. Nothing needs review right now.",
                ),
            );
        } else {
            const list = makeElement("ul", LIST);
            shown.forEach((item) => {
                const status = config.statuses[item.status] || {
                    label: item.status.replaceAll("_", " "),
                    group: "all",
                    badge: { tone: "neutral" },
                };
                const tone = status.badge?.tone || "neutral";
                const action = config.attentionActions[item.status] || "Open";
                const row = makeElement(
                    "li",
                    `${ATTN_ITEM} ${ATTN_TONES[tone] ?? ATTN_TONE_DEFAULT}`,
                );

                const badge = makeElement(
                    "span",
                    `${BADGE_BASE} ${BADGE_TONES[tone] ?? ""}`,
                );
                if (status.icon) {
                    const icon = makeElement("i", `ph ph-${status.icon}`);
                    icon.setAttribute("aria-hidden", "true");
                    badge.append(icon);
                }
                badge.append(document.createTextNode(status.label));
                row.append(badge);

                const info = makeElement("div", "min-w-0");
                const who = makeElement("p", ATTN_WHO);
                who.append(
                    makeElement("strong", "font-semibold", item.client),
                    makeElement("span", ATTN_REF, item.reference),
                );
                const meta = makeElement("p", ATTN_META);
                const eventDate = item.date
                    ? makeElement(
                          "time",
                          "",
                          dateLabel(item.date, {
                              month: "short",
                              day: "numeric",
                              year: "numeric",
                          }),
                      )
                    : makeElement("span", "", "Event date not set");
                if (item.date) eventDate.dateTime = item.date;
                const requested = item.submitted_at
                    ? makeElement("time", "", relativeTime(item.submitted_at))
                    : makeElement("span", "", "date unavailable");
                if (item.submitted_at) requested.dateTime = item.submitted_at;
                meta.append(
                    document.createTextNode(
                        `${item.package || "Unassigned"} · `,
                    ),
                    eventDate,
                    document.createTextNode(" · requested "),
                    requested,
                );
                info.append(who, meta);
                row.append(info);

                const actionLink = makeElement("a", ATTN_ACTION);
                actionLink.href = bookingsUrl({
                    status: status.group,
                    booking: item.id,
                });
                actionLink.setAttribute(
                    "aria-label",
                    `${action}, booking ${item.reference} from ${item.client}`,
                );
                actionLink.append(
                    document.createTextNode(action),
                    makeElement("i", "ph ph-arrow-right"),
                );
                actionLink.lastElementChild.setAttribute("aria-hidden", "true");
                row.append(actionLink);
                list.append(row);
            });
            fragment.append(list);

            if (items.length > shown.length) {
                const more = makeElement(
                    "a",
                    MORE_BELOW,
                    `View all ${items.length} in Bookings `,
                );
                more.href = bookingsUrl();
                const icon = makeElement("i", "ph ph-arrow-right");
                icon.setAttribute("aria-hidden", "true");
                more.append(icon);
                fragment.append(more);
            }
        }

        attentionTarget.replaceChildren(fragment);
    }

    function renderUpcoming(events) {
        const fragment = document.createDocumentFragment();
        const shown = events.slice(0, config.upcomingLimit);
        const now = new Date();
        const firstDate = shown[0]?.date || localDateString(now);
        const month = firstDate.slice(0, 7);
        const calendarUrl = new URL(config.calendarUrl, window.location.href);
        calendarUrl.searchParams.set("month", month);
        calendarLink.href = calendarUrl.toString();

        if (!shown.length) {
            fragment.append(makeElement("p", EMPTY, "No upcoming events."));
        } else {
            const list = makeElement("ul", LIST);
            shown.forEach((event) => {
                const date = dateAtLocalMidnight(event.date);
                const today = new Date(
                    now.getFullYear(),
                    now.getMonth(),
                    now.getDate(),
                );
                const days = Math.round((date - today) / 86400000);
                const when =
                    days === 0
                        ? "Today"
                        : days === 1
                          ? "Tomorrow"
                          : days > 1
                            ? `In ${days} days`
                            : "";
                const item = makeElement("a", UP_ITEM);
                item.href = bookingsUrl({ booking: event.id });
                item.setAttribute(
                    "aria-label",
                    `${event.event} for ${event.client}, ${dateLabel(
                        event.date,
                        {
                            month: "long",
                            day: "numeric",
                            year: "numeric",
                        },
                    )}. View booking`,
                );
                const dateTile = makeElement("time", UP_DATE);
                dateTile.dateTime = event.date;
                dateTile.append(
                    makeElement(
                        "span",
                        UP_MONTH,
                        dateLabel(event.date, { month: "short" }),
                    ),
                    makeElement("span", UP_DAY, String(date.getDate())),
                );
                const info = makeElement("span", UP_INFO);
                info.append(
                    makeElement("strong", "font-semibold", event.event),
                    makeElement(
                        "span",
                        UP_INFO_SUB,
                        `${event.client} · ${event.package}`,
                    ),
                );
                const whenBlock = makeElement("span", UP_WHEN);
                if (when)
                    whenBlock.append(
                        makeElement("strong", UP_WHEN_STRONG, when),
                    );
                if (event.time)
                    whenBlock.append(
                        makeElement("span", UP_WHEN_SPAN, event.time),
                    );
                item.append(dateTile, info, whenBlock);
                const listItem = makeElement("li");
                listItem.append(item);
                list.append(listItem);
            });
            fragment.append(list);

            if (events.length > shown.length) {
                const more = makeElement(
                    "a",
                    MORE_BELOW,
                    `View all ${events.length} in Calendar `,
                );
                more.href = calendarUrl.toString();
                const icon = makeElement("i", "ph ph-arrow-right");
                icon.setAttribute("aria-hidden", "true");
                more.append(icon);
                fragment.append(more);
            }
        }

        upcomingTarget.replaceChildren(fragment);
    }

    function renderPackageRate(packages) {
        const sorted = packages
            .filter((item) => item.count > 0)
            .sort((a, b) => b.count - a.count);
        const items = sorted.slice(0, config.chartTop);
        const rest = sorted.slice(config.chartTop);
        if (rest.length) {
            items.push({
                label: "Other",
                count: rest.reduce((sum, item) => sum + item.count, 0),
                other: true,
                more: rest.length,
            });
        }

        if (!items.length) {
            packageTarget.replaceChildren(
                makeElement(
                    "p",
                    EMPTY,
                    "No bookings yet, so there is nothing to chart.",
                ),
            );
            return;
        }

        const total = items.reduce((sum, item) => sum + item.count, 0);
        const body = makeElement("div", PR_BODY);
        const figure = makeElement("div", PR_FIGURE);
        const chart = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "svg",
        );
        chart.setAttribute("class", PR_CHART);
        chart.setAttribute("viewBox", "0 0 42 42");
        chart.setAttribute("role", "img");
        chart.setAttribute(
            "aria-label",
            `Bookings per package: ${items.map((item) => `${item.label} ${item.count}`).join(", ")}.`,
        );
        let offset = 0;
        items.forEach((item, index) => {
            const percent = (item.count / total) * 100;
            const dash = Math.max(percent - (items.length > 1 ? 0.6 : 0), 0);
            const circle = document.createElementNS(
                "http://www.w3.org/2000/svg",
                "circle",
            );
            circle.setAttribute("class", sliceClass(item, index));
            circle.setAttribute("cx", "21");
            circle.setAttribute("cy", "21");
            circle.setAttribute("r", "15.9155");
            circle.setAttribute("fill", "none");
            circle.setAttribute("stroke", "currentColor");
            circle.setAttribute("stroke-width", "5");
            circle.setAttribute("pathLength", "100");
            circle.setAttribute("stroke-dasharray", `${dash} ${100 - dash}`);
            circle.setAttribute(
                "stroke-dashoffset",
                offset ? String(-offset) : "0",
            );
            circle.setAttribute("transform", "rotate(-90 21 21)");
            chart.append(circle);
            offset += percent;
        });
        const center = makeElement("div", PR_CENTER);
        center.setAttribute("aria-hidden", "true");
        center.append(
            makeElement("strong", PR_TOTAL, total),
            makeElement(
                "span",
                PR_UNIT,
                `${total === 1 ? "booking" : "bookings"}`,
            ),
        );
        figure.append(chart, center);

        const legend = makeElement("ul", PR_LEGEND);
        items.forEach((item, index) => {
            const percent = ((item.count / total) * 100).toFixed(1);
            const row = makeElement("li", PR_ROW);
            const swatch = makeElement(
                "span",
                `${PR_SWATCH} ${sliceClass(item, index)}`,
            );
            swatch.setAttribute("aria-hidden", "true");
            const label =
                item.other || !item.id
                    ? makeElement("span", PR_NAME)
                    : makeElement("a", PR_NAME_LINK);
            if (item.other) {
                label.append(
                    document.createTextNode(item.label),
                    makeElement(
                        "span",
                        PR_MORE,
                        ` (${item.more} ${item.more === 1 ? "package" : "packages"})`,
                    ),
                );
            } else if (!item.id) {
                label.textContent = item.label;
            } else {
                label.textContent = item.label;
                label.href = bookingsUrl({ package: item.id });
                label.setAttribute(
                    "aria-label",
                    `${item.label}, ${item.count} bookings, ${percent} percent. View in Bookings`,
                );
            }
            const details = makeElement(
                "span",
                PR_PCT,
                `${item.count} ${item.count === 1 ? "booking" : "bookings"} · ${percent}%`,
            );
            row.append(swatch, label, details);
            legend.append(row);
        });
        body.append(figure, legend);
        packageTarget.replaceChildren(body);
    }

    async function loadDashboard() {
        const response = await fetch(dashboard.dataset.endpoint, {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
        });
        if (!response.ok) {
            throw new Error(`Dashboard request failed (${response.status}).`);
        }

        const data = await response.json();
        renderStats(data.stats);
        renderAttention(data.attention);
        renderUpcoming(data.upcomingEvents);
        renderPackageRate(data.packageRate);
        dashboard.setAttribute("aria-busy", "false");
    }

    loadDashboard().catch((error) => {
        console.error("Unable to load the admin dashboard:", error);
        dashboard.setAttribute("aria-busy", "false");
        [statsTarget, attentionTarget, upcomingTarget, packageTarget].forEach(
            (target) => {
                target.replaceChildren(
                    makeElement(
                        "p",
                        EMPTY,
                        "Dashboard data could not be loaded. Refresh the page to try again.",
                    ),
                );
            },
        );
        const errorMessage = makeElement(
            "p",
            ALERT_DANGER,
            "Dashboard data could not be loaded. Refresh the page to try again.",
        );
        errorMessage.setAttribute("role", "alert");
        dashboard.prepend(errorMessage);
    });
}
