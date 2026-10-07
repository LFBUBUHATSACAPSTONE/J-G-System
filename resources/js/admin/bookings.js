import { initFilterSheet } from "./filter-sheet.js";

// Admin Bookings page: filter / sort / search the rows (also from ?status= ?package= ?booking=
// links, see applyQueryParams), fill the booking modal, confirm destructive actions. The modal and confirm prompt are shared with the
// The modal itself is Bootstrap's (admin.js already loads Bootstrap for the sidebar offcanvas).

const getPath = (obj, path) =>
    path
        .split(".")
        .reduce((value, key) => (value == null ? undefined : value[key]), obj);

const isEmpty = (value) =>
    value === undefined || value === null || value === "";

//  List: filter, sort, search
function initList(body) {
    const rows = [...body.querySelectorAll("[data-booking-row]")];
    const sortSelect = document.querySelector("[data-bookings-sort]");
    const statusSelect = document.querySelector("[data-bookings-status]");
    const packageSelect = document.querySelector("[data-bookings-package]");
    const searchInput = document.querySelector("[data-bookings-search]");
    const emptyMessage = document.querySelector("[data-bookings-empty]");
    const count = document.querySelector("[data-bookings-count]");

    if (!rows.length) return;

    // Keys match config/admin/bookings.php 'sorts'.
    const byIndex = (a, b) => Number(a.dataset.index) - Number(b.dataset.index);
    const comparators = {
        default: byIndex,
        alpha: (a, b) =>
            a.dataset.name.localeCompare(b.dataset.name, undefined, {
                sensitivity: "base",
            }) || byIndex(a, b),
        date: (a, b) =>
            a.dataset.date.localeCompare(b.dataset.date) || byIndex(a, b),
    };

    function apply() {
        const status = statusSelect.value;
        const pkg = packageSelect.value;
        const query = searchInput.value.trim().toLowerCase();

        const visible = new Set(
            rows.filter(
                (row) =>
                    (status === "all" || row.dataset.statusGroup === status) &&
                    (pkg === "all" || row.dataset.package === pkg) &&
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

        emptyMessage.hidden = visible.size > 0;
        count.textContent = `Showing ${visible.size} of ${rows.length} bookings`;

        // Lets the filter bar (chips + badge) follow along, including after deep-link params.
        document.dispatchEvent(new CustomEvent("admin:filters-applied"));
    }

    [sortSelect, statusSelect, packageSelect].forEach((el) =>
        el.addEventListener("change", apply),
    );
    searchInput.addEventListener("input", apply);

    applyQueryParams({
        rows,
        sortSelect,
        statusSelect,
        packageSelect,
        searchInput,
        apply,
    });
}

// ---- Deep links (Dashboard cards, attention list, upcoming events, package legend) ----------
// The page reads these optional query params on load:
//   ?status=approved|pending|payment|cancelled|completed   (keys of 'status_filters')
//   ?package=<package id>                                  (same ids as the Package filter)
//   ?sort=default|alpha|date   ?q=<search text>
//   ?booking=<booking id>   scrolls to that row and opens its View Details modal
// Unknown values are ignored, so a stale link still opens the page. Nothing is written to the URL.
function applyQueryParams({
    rows,
    sortSelect,
    statusSelect,
    packageSelect,
    searchInput,
    apply,
}) {
    const params = new URLSearchParams(window.location.search);
    if (![...params.keys()].length) return;

    const setIfValid = (select, value) => {
        if (
            value &&
            [...select.options].some((option) => option.value === value)
        )
            select.value = value;
    };

    setIfValid(statusSelect, params.get("status"));
    setIfValid(packageSelect, params.get("package"));
    setIfValid(sortSelect, params.get("sort"));
    if (params.get("q")) searchInput.value = params.get("q");
    apply();

    const id = params.get("booking");
    if (!id) return;

    const row = rows.find((candidate) => candidate.dataset.bookingId === id);
    if (!row) return;

    // The filters in the link may hide the booking (for example a stale status): show everything.
    if (row.hidden) {
        statusSelect.value = "all";
        packageSelect.value = "all";
        searchInput.value = "";
        apply();
    }

    row.scrollIntoView({ block: "center" });
    // A real click on the row's own button, so Bootstrap sets relatedTarget and the modal fills as usual.
    row.querySelector('[data-bs-toggle="modal"]')?.click();

    // Drop ?booking= so a refresh does not reopen the modal; the filters stay in the URL.
    params.delete("booking");
    const query = params.toString();
    window.history.replaceState(
        null,
        "",
        window.location.pathname +
            (query ? `?${query}` : "") +
            window.location.hash,
    );
}

//  Modal: fill from the clicked row, Edit toggle
function initModal(modal) {
    const form = modal.querySelector("form");
    const editButton = modal.querySelector("[data-booking-edit]");
    const saveButton = modal.querySelector("[data-booking-save]");
    const editable = [...modal.querySelectorAll("[data-editable]")];
    const urlTemplate = modal.dataset.updateUrlTemplate;

    let current = null;
    let editing = false;

    function fill(booking) {
        modal.querySelectorAll("[data-booking-field]").forEach((el) => {
            const value = getPath(booking, el.dataset.bookingField);

            if (
                el instanceof HTMLInputElement ||
                el instanceof HTMLSelectElement
            ) {
                // Empty inputs show the "--" placeholder from the markup, and submit as empty.
                el.value = isEmpty(value) ? "" : value;
            } else {
                el.textContent = isEmpty(value) ? "--" : value;
            }

            if (el.dataset.bookingState) {
                el.dataset.state =
                    getPath(booking, el.dataset.bookingState) ?? "";
            }
        });

        form.setAttribute(
            "action",
            urlTemplate.replace("__ID__", encodeURIComponent(booking.id)),
        );
    }

    function setEditing(on) {
        const wasEditing = editing;
        editing = on;

        modal.classList.toggle("is-editing", on);
        editable.forEach((el) => {
            if (el instanceof HTMLSelectElement) el.disabled = !on;
            else el.readOnly = !on;
        });
        saveButton.hidden = !on;
        // Label and icon swap in place (the button holds an icon, so textContent would wipe it).
        const label = editButton.querySelector("[data-booking-edit-label]");
        const icon = editButton.querySelector("[data-booking-edit-icon]");
        if (label) label.textContent = on ? "Cancel" : "Edit";
        if (icon) icon.className = `ph ${on ? "ph-x" : "ph-pencil-simple"}`;

        if (on) editable[0]?.focus();
        // Cancelling an edit puts the original values back.
        if (!on && wasEditing && current) fill(current);
    }

    modal.addEventListener("show.bs.modal", (event) => {
        const trigger = event.relatedTarget;
        if (!trigger?.dataset.booking) return;

        current = JSON.parse(trigger.dataset.booking);
        setEditing(false);
        fill(current);

        // History: finished and cancelled bookings are records, so they cannot be edited.
        editButton.hidden = current.editable === false;
    });

    modal.addEventListener("hidden.bs.modal", () => setEditing(false));

    editButton.addEventListener("click", () => setEditing(!editing));

    // Enter inside a read-only field must not submit the form.
    form.addEventListener("submit", (event) => {
        if (!editing) event.preventDefault();
    });
}

//  Confirm destructive actions (Decline / Cancel)
function initConfirm() {
    document.addEventListener("submit", async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
            return;
        }

        if (
            !form.matches(".admin-bookings__action-form") &&
            !form.closest("#bookingModal")
        ) {
            return;
        }

        event.preventDefault();
        const feedback = document.querySelector("[data-bookings-feedback]");
        if (feedback) {
            feedback.hidden = true;
            feedback.textContent = "";
            feedback.classList.remove("admin-alert--danger");
        }

        try {
            // Status buttons are named "action", which can shadow the form.action
            // property in some browsers. Read the literal attribute to get the URL.
            const actionUrl = form.getAttribute("action");
            if (!actionUrl) {
                throw new Error("The booking action URL is missing.");
            }

            const response = await fetch(actionUrl, {
                method: form.getAttribute("method") || "GET",
                body: new FormData(form, event.submitter),
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const result = await response.json();

            if (!response.ok) {
                const message =
                    result.message ||
                    Object.values(result.errors || {})
                        .flat()
                        .join(" ");
                throw new Error(message || "The booking could not be updated.");
            }

            window.location.reload();
        } catch (error) {
            if (!feedback) return;
            feedback.textContent =
                error instanceof Error
                    ? error.message
                    : "The booking could not be updated.";
            feedback.classList.add("admin-alert--danger");
            feedback.hidden = false;
            feedback.focus?.();
        }
    });
}

function init() {
    // The modal listener must exist before initList runs: the ?booking= deep link clicks a row's
    // View Details button on load, and the modal fills in its show.bs.modal handler.
    const modal = document.getElementById("bookingModal");
    if (modal) initModal(modal);

    const body = document.querySelector("[data-bookings-body]");
    if (body) initList(body);

    // After initList, so chips reflect any ?status= / ?package= deep link.
    document.querySelectorAll("[data-filter-bar]").forEach(initFilterSheet);

    initConfirm();
}

if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", init);
else init();
