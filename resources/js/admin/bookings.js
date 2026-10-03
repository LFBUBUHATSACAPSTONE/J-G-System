// Admin Bookings page: filter / sort / search the rows, fill the booking modal, confirm
// destructive actions. The modal and confirm prompt are shared with the
// Booking History page (history.js owns that page's list).
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

    // Keys match config/admin-bookings.php 'sorts'.
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
    }

    [sortSelect, statusSelect, packageSelect].forEach((el) =>
        el.addEventListener("change", apply),
    );
    searchInput.addEventListener("input", apply);
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

        form.action = urlTemplate.replace(
            "__ID__",
            encodeURIComponent(booking.id),
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
        editButton.textContent = on ? "Cancel edit" : "Edit";

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
    document.addEventListener("submit", (event) => {
        const message = event.target.dataset?.confirm;
        if (message && !window.confirm(message)) event.preventDefault();
    });
}

function init() {
    const body = document.querySelector("[data-bookings-body]");
    if (body) initList(body);

    const modal = document.getElementById("bookingModal");
    if (modal) initModal(modal);

    initConfirm();
}

if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", init);
else init();
