const dashboard = document.querySelector("[data-admin-dashboard]");

if (dashboard) {
    const config = JSON.parse(dashboard.dataset.config);
    const statsTarget = dashboard.querySelector("[data-dashboard-stats]");
    const attentionTarget = dashboard.querySelector("[data-dashboard-attention]");
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

        return new Intl.RelativeTimeFormat(undefined, { numeric: "auto" }).format(
            Math.round(difference / divisor),
            unit,
        );
    };

    function renderStats(stats) {
        const fragment = document.createDocumentFragment();

        config.cards.forEach((card) => {
            const value = stats[card.key];
            if (!value) return;

            const article = makeElement(
                "article",
                `admin-card admin-stat admin-stat--link${card.featured ? " admin-stat--featured" : ""}`,
            );
            const heading = makeElement("h2", "admin-stat__label");
            const link = makeElement("a", "admin-stat__link");
            link.href = bookingsUrl(
                card.status === "all" ? {} : { status: card.status },
            );
            link.append(
                document.createTextNode(card.label),
                makeElement(
                    "span",
                    "visually-hidden",
                    `: ${value.value}. View in Bookings`,
                ),
                makeElement("i", "ph ph-arrow-up-right admin-stat__go"),
            );
            link.lastElementChild.setAttribute("aria-hidden", "true");
            heading.append(link);
            article.append(heading);

            const body = makeElement("div", "admin-stat__body");
            body.append(makeElement("p", "admin-stat__value", value.value));
            if (value.change !== null && value.change !== undefined) {
                const change = Number(value.change);
                const direction = change > 0 ? "up" : change < 0 ? "down" : "flat";
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
                    `admin-stat__delta admin-stat__delta--${tone}`,
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
                    makeElement("span", "visually-hidden", spoken),
                );
                delta.firstElementChild.setAttribute("aria-hidden", "true");
                delta.children[1].setAttribute("aria-hidden", "true");
                body.append(delta);
            }
            article.append(body);
            article.append(
                makeElement(
                    "p",
                    "admin-stat__caption",
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
                    "admin-empty",
                    "You're all caught up. Nothing needs review right now.",
                ),
            );
        } else {
            const list = makeElement("ul", "admin-attn");
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
                    `admin-attn__item admin-attn__item--${tone}`,
                );

                const badge = makeElement(
                    "span",
                    `admin-badge admin-badge--${tone}`,
                );
                if (status.icon) {
                    const icon = makeElement("i", `ph ph-${status.icon}`);
                    icon.setAttribute("aria-hidden", "true");
                    badge.append(icon);
                }
                badge.append(document.createTextNode(status.label));
                row.append(badge);

                const info = makeElement("div", "admin-attn__info");
                const who = makeElement("p", "admin-attn__who");
                who.append(
                    makeElement("strong", "", item.client),
                    makeElement("span", "admin-attn__ref", item.reference),
                );
                const meta = makeElement("p", "admin-attn__meta");
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
                    document.createTextNode(`${item.package || "Unassigned"} · `),
                    eventDate,
                    document.createTextNode(" · requested "),
                    requested,
                );
                info.append(who, meta);
                row.append(info);

                const actionLink = makeElement("a", "admin-attn__action");
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
                actionLink.lastElementChild.setAttribute(
                    "aria-hidden",
                    "true",
                );
                row.append(actionLink);
                list.append(row);
            });
            fragment.append(list);

            if (items.length > shown.length) {
                const more = makeElement(
                    "a",
                    "admin-card__more",
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
            fragment.append(makeElement("p", "admin-empty", "No upcoming events."));
        } else {
            const list = makeElement("ul", "admin-upcoming");
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
                const item = makeElement("a", "admin-upcoming__item");
                item.href = bookingsUrl({ booking: event.id });
                item.setAttribute(
                    "aria-label",
                    `${event.event} for ${event.client}, ${dateLabel(event.date, {
                        month: "long",
                        day: "numeric",
                        year: "numeric",
                    })}. View booking`,
                );
                const dateTile = makeElement("time", "admin-upcoming__date");
                dateTile.dateTime = event.date;
                dateTile.append(
                    makeElement(
                        "span",
                        "admin-upcoming__month",
                        dateLabel(event.date, { month: "short" }),
                    ),
                    makeElement(
                        "span",
                        "admin-upcoming__day",
                        String(date.getDate()),
                    ),
                );
                const info = makeElement("span", "admin-upcoming__info");
                info.append(
                    makeElement("strong", "", event.event),
                    makeElement(
                        "span",
                        "",
                        `${event.client} · ${event.package}`,
                    ),
                );
                const whenBlock = makeElement("span", "admin-upcoming__when");
                if (when) whenBlock.append(makeElement("strong", "", when));
                if (event.time) whenBlock.append(makeElement("span", "", event.time));
                item.append(dateTile, info, whenBlock);
                const listItem = makeElement("li");
                listItem.append(item);
                list.append(listItem);
            });
            fragment.append(list);
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
                    "admin-empty",
                    "No bookings yet, so there is nothing to chart.",
                ),
            );
            return;
        }

        const total = items.reduce((sum, item) => sum + item.count, 0);
        const body = makeElement("div", "admin-package-rate__body");
        const figure = makeElement("div", "admin-package-rate__figure");
        const chart = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "svg",
        );
        chart.setAttribute("class", "admin-package-rate__chart");
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
            circle.setAttribute(
                "class",
                item.other ? "admin-chart--other" : `admin-chart--${(index % 6) + 1}`,
            );
            circle.setAttribute("cx", "21");
            circle.setAttribute("cy", "21");
            circle.setAttribute("r", "15.9155");
            circle.setAttribute("fill", "none");
            circle.setAttribute("stroke", "currentColor");
            circle.setAttribute("stroke-width", "5");
            circle.setAttribute("pathLength", "100");
            circle.setAttribute("stroke-dasharray", `${dash} ${100 - dash}`);
            circle.setAttribute("stroke-dashoffset", offset ? String(-offset) : "0");
            circle.setAttribute("transform", "rotate(-90 21 21)");
            chart.append(circle);
            offset += percent;
        });
        const center = makeElement("div", "admin-package-rate__center");
        center.setAttribute("aria-hidden", "true");
        center.append(
            makeElement("strong", "", total),
            makeElement("span", "", `${total === 1 ? "booking" : "bookings"}`),
        );
        figure.append(chart, center);

        const legend = makeElement("ul", "admin-package-rate__legend");
        items.forEach((item, index) => {
            const percent = ((item.count / total) * 100).toFixed(1);
            const row = makeElement("li");
            const swatch = makeElement(
                "span",
                `admin-package-rate__swatch ${item.other ? "admin-chart--other" : `admin-chart--${(index % 6) + 1}`}`,
            );
            swatch.setAttribute("aria-hidden", "true");
            const label = item.other || !item.id
                ? makeElement("span", "admin-package-rate__name")
                : makeElement("a", "admin-package-rate__name admin-package-rate__name--link");
            if (item.other) {
                label.append(
                    document.createTextNode(item.label),
                    makeElement(
                        "span",
                        "admin-package-rate__more",
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
                "admin-package-rate__pct",
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
                        "admin-empty",
                        "Dashboard data could not be loaded. Refresh the page to try again.",
                    ),
                );
            },
        );
        const errorMessage = makeElement(
            "p",
            "admin-alert admin-alert--danger",
            "Dashboard data could not be loaded. Refresh the page to try again.",
        );
        errorMessage.setAttribute("role", "alert");
        dashboard.prepend(errorMessage);
    });
}
