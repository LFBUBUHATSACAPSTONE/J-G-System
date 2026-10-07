/**
 * Event Schedule step (booking flow) — submit (AJAX) + live field
 * feedback. Same shell as event-information.js: novalidate on the form,
 * fetch() on submit, inline errors under each field.
 *
 * Two custom controls unique to this step:
 *   - a month calendar (data-calendar) built entirely in JS. Clicking a
 *     day selects it as a one-day event (start and end both land on that
 *     date); clicking a later day extends it into a multi-day range;
 *     clicking again after a range is set starts a new one-day selection.
 *     Only today and later are selectable — the Previous-month arrow is
 *     hidden while viewing the earliest navigable month.
 *   - a time field (data-time-input) paired with an AM/PM toggle
 *     (data-ampm-group) for Start In / End In.
 *
 * The Continue button lives outside <form> (in the aside action column)
 * and is wired via form="event-schedule-form". The Previous button only
 * dispatches a bubbling `booking:previous` CustomEvent, same as
 * event-information.js.
 *
 * Event limit (config/scheduling.php, max 3 approved events per day):
 *   - GET `booking.availability` ?month=YYYY-MM -> { limit, month, full: ["YYYY-MM-DD", …] }
 *     is called when the step opens and on every month change. Full days
 *     get `.is-full`, aria-disabled and a "fully booked" label, and can't
 *     be picked or included in a range. If the call fails the calendar
 *     stays usable: the server still rejects a full day on submit.
 *   - A `booking:schedule-conflict` event (detail: { fullDates, message })
 *     marks those days full, clears the selection and shows the message.
 *     This step fires it on a 422 that carries `full_dates`; the Booking
 *     Summary step fires it too, and booking-flow.js sends the client back here.
 *
 * Reschedule mode: when the calendar has
 * data-reschedule-month="YYYY-MM" it opens on that month, both month arrows are hidden and
 * disabled, and no other month can be reached. If no day is left (all past or full) the calendar
 * and Continue are replaced by an empty state. The submit also sends `reschedule_id` (from
 * #bookingFlow[data-reschedule-id]). The server takes the month from the STORED booking and
 * answers 422 `errors.event_start_date` for dates outside it, and 403 {message} when the booking
 * can't be rescheduled (not user-cancelled, already rescheduled, month passed).
 *
 * Expected back-end contract for `booking.event-schedule`:
 *   200 JSON -> saved, caller advances to the next step
 *   422 {"message": "…", "errors": {"event_start_date": ["…"], …}} -> rejected
 *   422 {"message": "…", "errors": {…}, "full_dates": ["YYYY-MM-DD", …]}
 *       -> a day in the range is at the event limit (race: someone else took it)
 */

import { getCsrfToken } from "../auth/csrf.js";
import { setFieldState } from "../auth/validation.js";

const FORM_ID = "event-schedule-form";
const WEEKDAY_LABELS = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
const MONTH_LABELS = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

function initEventSchedule() {
    const form = document.getElementById(FORM_ID);
    if (!form) return;

    initCalendar(form);
    form.querySelectorAll("[data-time-input]").forEach((input) =>
        initTimeField(input),
    );

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitEventSchedule(form);
    });

    form.addEventListener("input", () => clearError(form));
    form.addEventListener("change", () => clearError(form));

    // Scoped to this step's own wrapper — see the matching note in
    // client-information.js for why (every step's markup now coexists
    // in the DOM, toggled via d-none by booking-flow.js).
    const step = form.closest(".booking-flow__view") || document;
    const previousBtn = step.querySelector("[data-booking-previous]");
    previousBtn?.addEventListener("click", () => {
        previousBtn.dispatchEvent(
            new CustomEvent("booking:previous", { bubbles: true }),
        );
    });
}

/*
 Calendar: renders a month grid into [data-calendar-days] from scratch
 on every render() call. `view` tracks the currently displayed month;
 `selection` tracks the chosen start/end dates (as local YYYY-MM-DD
 strings, so there's no timezone drift between the grid and the hidden
 inputs). Only today-or-later dates are selectable.
*/
function initCalendar(form) {
    const root = form.querySelector("[data-calendar]");
    if (!root) return;

    const label = root.querySelector("[data-calendar-label]");
    const daysEl = root.querySelector("[data-calendar-days]");
    const prevBtn = root.querySelector("[data-calendar-prev]");
    const nextBtn = root.querySelector("[data-calendar-next]");
    const startInput = root.querySelector("[data-start-date-input]");
    const endInput = root.querySelector("[data-end-date-input]");
    const rangeLabel = form.querySelector("[data-selected-range]");

    const today = startOfDay(new Date());
    // Reschedule mode: locked to one month ("YYYY-MM"), see the header comment.
    const lockedMonth = /^\d{4}-(0[1-9]|1[0-2])$/.test(
        root.dataset.rescheduleMonth ?? "",
    )
        ? root.dataset.rescheduleMonth
        : null;
    const minView = lockedMonth
        ? {
              year: Number(lockedMonth.slice(0, 4)),
              month: Number(lockedMonth.slice(5, 7)) - 1,
          }
        : { year: today.getFullYear(), month: today.getMonth() };
    const view = { ...minView };
    const emptyEl = form.querySelector("[data-reschedule-empty]");
    const continueBtn = document.querySelector(
        `[form="${form.id}"][type="submit"]`,
    );
    const selection = { start: null, end: null };
    const availabilityUrl = root.dataset.availabilityUrl;
    const fullDates = new Set(); // days at the event limit, as local YYYY-MM-DD strings

    const render = () => {
        label.textContent = `${MONTH_LABELS[view.month]} ${view.year}`;
        prevBtn.classList.toggle(
            "is-invisible",
            view.year === minView.year && view.month === minView.month,
        );
        if (lockedMonth) {
            [prevBtn, nextBtn].forEach((btn) => {
                btn.classList.add("is-invisible");
                btn.disabled = true;
                btn.tabIndex = -1;
            });
        }

        daysEl.innerHTML = "";
        const firstDay = new Date(view.year, view.month, 1);
        const daysInMonth = new Date(view.year, view.month + 1, 0).getDate();

        for (let i = 0; i < firstDay.getDay(); i++) {
            daysEl.appendChild(document.createElement("span"));
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(view.year, view.month, day);
            const iso = toIso(date);
            const weekday = date.getDay();

            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "event-schedule__day";
            btn.textContent = day;
            btn.dataset.date = iso;
            if (weekday === 0 || weekday === 6) btn.classList.add("is-weekend");
            if (iso === selection.start || iso === selection.end) {
                btn.classList.add("is-selected");
            }
            if (date < today) btn.disabled = true;
            if (fullDates.has(iso)) {
                // aria-disabled (not disabled) so the label is still announced.
                btn.classList.add("is-full");
                btn.setAttribute("aria-disabled", "true");
                btn.setAttribute(
                    "aria-label",
                    `${formatDisplayDate(iso)}, fully booked`,
                );
                btn.title = "Fully booked";
            }

            btn.addEventListener("click", () => {
                if (btn.classList.contains("is-full")) return;
                selectDate(iso);
            });
            daysEl.appendChild(btn);
        }

        updateRangeLabel();
        updateEmptyState();
    };

    // Reschedule mode only: no selectable day left in the locked month -> empty state, no Continue.
    const updateEmptyState = () => {
        if (!lockedMonth || !emptyEl) return;
        const daysInMonth = new Date(view.year, view.month + 1, 0).getDate();
        let open = false;
        for (let day = 1; day <= daysInMonth && !open; day++) {
            const date = new Date(view.year, view.month, day);
            open = date >= today && !fullDates.has(toIso(date));
        }
        emptyEl.classList.toggle("d-none", open);
        form.querySelector(".event-schedule__body")?.classList.toggle(
            "d-none",
            !open,
        );
        continueBtn?.classList.toggle("d-none", !open);
    };

    const updateRangeLabel = () => {
        if (!rangeLabel) return;
        if (!selection.start) {
            rangeLabel.textContent = "Select a date on the calendar.";
            rangeLabel.classList.remove("has-value");
            return;
        }
        rangeLabel.textContent =
            selection.start === selection.end
                ? formatDisplayDate(selection.start)
                : `${formatDisplayDate(selection.start)} – ${formatDisplayDate(selection.end)}`;
        rangeLabel.classList.add("has-value");
    };

    const monthKey = () =>
        `${view.year}-${String(view.month + 1).padStart(2, "0")}`;

    const clearSelection = () => {
        selection.start = null;
        selection.end = null;
        startInput.value = "";
        endInput.value = "";
    };

    // A range may not cover a full day, even one in between its ends.
    const rangeHasFull = (from, to) =>
        daysBetween(from, to).some((d) => fullDates.has(d));

    // Fresh data can turn the picked day(s) full. Never keep such a selection.
    const dropSelectionIfFull = () => {
        if (!selection.start || !rangeHasFull(selection.start, selection.end))
            return;
        clearSelection();
        showError(
            form,
            "The dates you selected just became fully booked. Please choose again.",
            [root],
        );
    };

    // Replaces what we know about the viewed month with the server's answer, so a day that
    // was freed (a cancellation) opens up again.
    const loadAvailability = async () => {
        if (!availabilityUrl) return;
        const key = monthKey();
        try {
            const response = await fetch(`${availabilityUrl}?month=${key}`, {
                headers: { Accept: "application/json" },
            });
            if (!response.ok) return;
            const data = await response.json();
            [...fullDates]
                .filter((d) => d.startsWith(key))
                .forEach((d) => fullDates.delete(d));
            (data.full ?? []).forEach((d) => fullDates.add(d));
            dropSelectionIfFull();
            render();
        } catch {
            // Fail open: the server still refuses a full day when the step is submitted.
        }
    };

    const selectDate = (iso) => {
        if (fullDates.has(iso)) return;
        if (!selection.start || selection.start !== selection.end) {
            // Nothing picked yet, or a full multi-day range was already
            // picked — start a fresh selection. A single click alone is
            // enough to book a one-day event: start and end both land on
            // the same date.
            selection.start = iso;
            selection.end = iso;
        } else if (iso < selection.start) {
            // Earlier than the currently-picked day — move the whole
            // (still single-day) selection there.
            selection.start = iso;
            selection.end = iso;
        } else {
            // Same day again (no-op) or a later day — extend into a
            // multi-day range, unless it would cover a fully booked day.
            if (rangeHasFull(selection.start, iso)) {
                showError(
                    form,
                    "That range includes a fully booked day. Pick dates without one.",
                    [root],
                );
                return;
            }
            selection.end = iso;
        }
        startInput.value = selection.start ?? "";
        endInput.value = selection.end ?? "";
        startInput.dispatchEvent(new Event("change", { bubbles: true }));
        render();
    };

    prevBtn.addEventListener("click", () => {
        view.month -= 1;
        if (view.month < 0) {
            view.month = 11;
            view.year -= 1;
        }
        render();
        loadAvailability();
    });

    nextBtn.addEventListener("click", () => {
        view.month += 1;
        if (view.month > 11) {
            view.month = 0;
            view.year += 1;
        }
        render();
        loadAvailability();
    });

    // A day filled up after the client picked it (this step's 422, or the Booking Summary's).
    document.addEventListener("booking:schedule-conflict", (event) => {
        const { fullDates: dates = [], message } = event.detail ?? {};
        dates.forEach((d) => fullDates.add(d));
        clearSelection();

        // Show the month of the first full day so the client sees what changed.
        const first = [...dates].sort()[0];
        if (first && !lockedMonth) {
            const [y, m] = first.split("-").map(Number);
            if (
                y > minView.year ||
                (y === minView.year && m - 1 >= minView.month)
            ) {
                view.year = y;
                view.month = m - 1;
            }
        }
        render();
        showError(
            form,
            message ||
                "That day is no longer available. Please choose another date.",
            [root],
        );
        loadAvailability();
    });

    // The other steps live in the same page, so refresh whenever this one becomes visible again.
    const step = form.closest(".booking-flow__view");
    if (step) {
        new MutationObserver(() => {
            if (!step.classList.contains("d-none")) loadAvailability();
        }).observe(step, { attributes: true, attributeFilter: ["class"] });
    }

    render();
    loadAvailability();
}

function startOfDay(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    return d;
}

// Every local YYYY-MM-DD from `from` to `to`, both included.
function daysBetween(from, to) {
    const days = [];
    const [y, m, d] = from.split("-").map(Number);
    const cursor = new Date(y, m - 1, d);
    for (let guard = 0; guard < 400; guard++) {
        const iso = toIso(cursor);
        if (iso > to) break;
        days.push(iso);
        cursor.setDate(cursor.getDate() + 1);
    }
    return days;
}

function toIso(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

// "2026-12-22" -> "December 22, 2026", for the selected-range label.
function formatDisplayDate(iso) {
    const [y, m, d] = iso.split("-").map(Number);
    return `${MONTH_LABELS[m - 1]} ${d}, ${y}`;
}

/*
 Time field: masks the input to a strict HH:MM shape as the user types —
 strips anything but digits, auto-inserts the colon, and clamps the hour
 to 1-12 and the minutes to 00-59 so it's impossible to type anything
 that isn't a valid 12-hour time. Also wires the paired AM/PM buttons as
 a two-state toggle (aria-pressed).
*/
function initTimeField(input) {
    input.addEventListener("input", () => {
        input.value = formatTimeDigits(input.value);
    });

    const group = input
        .closest(".event-schedule__time-field")
        ?.querySelector("[data-ampm-group]");
    if (!group) return;

    const buttons = [...group.querySelectorAll("[data-ampm]")];

    // Sliding pill behind the buttons — one div, moved with
    // translateX() to whichever button is active, so AM/PM glides
    // instead of the highlight jumping.
    const thumb = document.createElement("div");
    thumb.className = "event-schedule__ampm-thumb";
    group.prepend(thumb);

    const moveThumb = () => {
        const activeIndex = buttons.findIndex(
            (b) => b.getAttribute("aria-pressed") === "true",
        );
        thumb.style.transform = `translateX(${Math.max(activeIndex, 0) * 100}%)`;
    };
    moveThumb();

    buttons.forEach((btn) => {
        btn.addEventListener("click", () => {
            buttons.forEach((b) => b.setAttribute("aria-pressed", "false"));
            btn.setAttribute("aria-pressed", "true");
            moveThumb();
            group.dispatchEvent(new Event("change", { bubbles: true }));
        });
    });
}

/*
 Turns raw keystrokes into a masked H:MM / HH:MM string. Only "10", "11"
 and "12" ever use two hour digits — typing "9" then "3" reads as 9:03,
 not the impossible 09/3 — and the minute is clamped a digit at a time
 (tens digit capped at 5, full pair capped at 59).
*/
function formatTimeDigits(raw) {
    const digits = raw.replace(/\D/g, "").slice(0, 4);
    if (!digits) return "";

    const hourLen =
        digits.length >= 2 && ["10", "11", "12"].includes(digits.slice(0, 2))
            ? 2
            : 1;

    let hour = digits.slice(0, hourLen);
    let minute = digits.slice(hourLen, hourLen + 2);

    const hourNum = Number(hour);
    if (hourNum === 0) hour = "";
    else if (hourNum > 12) hour = "12";

    if (minute.length === 1 && Number(minute) > 5) minute = "5";
    if (minute.length === 2 && Number(minute) > 59) minute = "59";

    return minute ? `${hour}:${minute}` : hour;
}

function getTimeError(value, label) {
    if (!value.trim()) return `${label} is required.`;
    if (!/^([1-9]|1[0-2]):[0-5][0-9]$/.test(value.trim()))
        return `Enter a valid time as HH:MM, e.g. 9:00.`;
    return "";
}

function getPeriod(group) {
    return group.querySelector('[aria-pressed="true"]')?.dataset.ampm ?? "AM";
}

// Minutes since midnight for a masked "H:MM"/"HH:MM" value + AM/PM period.
// Returns null if the value isn't a complete, parseable time.
function toMinutes(value, period) {
    const match = /^([1-9]|1[0-2]):([0-5][0-9])$/.exec(value.trim());
    if (!match) return null;
    let hour = Number(match[1]);
    const minute = Number(match[2]);
    if (period === "AM" && hour === 12) hour = 0;
    if (period === "PM" && hour !== 12) hour += 12;
    return hour * 60 + minute;
}

async function submitEventSchedule(form) {
    const field = (name) => form.querySelector(`[name="${name}"]`);
    const startDateInput = field("event_start_date");
    const endDateInput = field("event_end_date");
    const startTimeInput = field("start_time");
    const endTimeInput = field("end_time");
    const startAmpmGroup = startTimeInput
        .closest(".event-schedule__field")
        .querySelector("[data-ampm-group]");
    const endAmpmGroup = endTimeInput
        .closest(".event-schedule__field")
        .querySelector("[data-ampm-group]");
    const calendar = form.querySelector("[data-calendar]");
    const submitBtn = document.querySelector(
        `[form="${form.id}"][type="submit"]`,
    );

    const rescheduleId = form.closest("#bookingFlow")?.dataset.rescheduleId;

    clearError(form);

    const checks = [
        [
            calendar,
            startDateInput.value && endDateInput.value
                ? ""
                : "Select a start and end date on the calendar.",
            "event_start_date",
        ],
        [
            startTimeInput.closest(".event-schedule__time-field"),
            getTimeError(startTimeInput.value, "Start time"),
            "start_time",
        ],
        [
            endTimeInput.closest(".event-schedule__time-field"),
            getTimeError(endTimeInput.value, "End time"),
            "end_time",
        ],
        [
            endTimeInput.closest(".event-schedule__time-field"),
            // Only a same-day event has a strict, checkable ordering —
            // a multi-day range can legitimately end earlier in the
            // clock than it starts (it just spans into the next day).
            startDateInput.value &&
            startDateInput.value === endDateInput.value &&
            !getTimeError(startTimeInput.value, "Start time") &&
            !getTimeError(endTimeInput.value, "End time") &&
            toMinutes(endTimeInput.value, getPeriod(endAmpmGroup)) <=
                toMinutes(startTimeInput.value, getPeriod(startAmpmGroup))
                ? "End time must be later than start time."
                : "",
            "end_time",
        ],
    ];
    const firstFailure = checks.find(([, message]) => message);
    if (firstFailure) {
        const [input, message, fieldName] = firstFailure;
        const errorEl = form.querySelector(`[data-field-error="${fieldName}"]`);
        setFieldState(input, errorEl, message);
        return;
    }

    if (submitBtn) submitBtn.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(form),
            },
            body: JSON.stringify({
                ...(rescheduleId ? { reschedule_id: rescheduleId } : {}),
                event_start_date: startDateInput.value,
                event_end_date: endDateInput.value,
                start_time: `${startTimeInput.value.trim()} ${getPeriod(startAmpmGroup)}`,
                end_time: `${endTimeInput.value.trim()} ${getPeriod(endAmpmGroup)}`,
            }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));

            // A day in the range is at the event limit: the calendar takes it from here.
            if (Array.isArray(data.full_dates) && data.full_dates.length) {
                form.dispatchEvent(
                    new CustomEvent("booking:schedule-conflict", {
                        bubbles: true,
                        detail: {
                            fullDates: data.full_dates,
                            message: data.message,
                        },
                    }),
                );
                return;
            }

            const errors = data.errors ?? {};
            const invalidInputs = Object.keys(errors)
                .map((name) => {
                    if (
                        name === "event_start_date" ||
                        name === "event_end_date"
                    )
                        return calendar;
                    if (name === "start_time")
                        return startTimeInput.closest(
                            ".event-schedule__time-field",
                        );
                    if (name === "end_time")
                        return endTimeInput.closest(
                            ".event-schedule__time-field",
                        );
                    return form.querySelector(`[name="${name}"]`);
                })
                .filter(Boolean);
            showError(
                form,
                Object.values(errors)[0]?.[0] ||
                    data.message ||
                    "We couldn't save the schedule. Please review the fields above and try again.",
                invalidInputs,
            );
            return;
        }

        form.dispatchEvent(
            new CustomEvent("booking:event-schedule-saved", { bubbles: true }),
        );
    } catch {
        showError(
            form,
            "Something went wrong on our end. Please check your connection and try again.",
        );
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
}

function showError(form, message, invalidInputs = []) {
    const errorEl = form.querySelector("[data-event-schedule-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
    invalidInputs.forEach((input) => input?.classList.add("is-invalid"));
}

function clearError(form) {
    const errorEl = form.querySelector("[data-event-schedule-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    form.querySelectorAll(
        ".event-schedule__time-field, [data-calendar]",
    ).forEach((el) => el.classList.remove("is-invalid"));
}

document.addEventListener("DOMContentLoaded", initEventSchedule);

export { initEventSchedule };
