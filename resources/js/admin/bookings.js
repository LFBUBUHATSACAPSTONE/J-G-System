import { initFilterSheet } from "./filter-sheet.js";
import {
    getRequiredError,
    getEmailError,
    getContactNumberError,
    getNameError,
    isContactNumberUntouched,
    wireLiveField,
    wireLiveFieldImmediate,
    setFieldState,
} from "../auth/validation.js"; // shared with the user-side booking form

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

//  Modal: fill from the clicked row, Edit toggle, validation
//  Validation mirrors the user-side booking form (resources/js/booking/client-information.js and
//  event-information.js): the same helpers from ../auth/validation.js, the same messages, the same
//  "filter while typing" rules (letters-only name, digits-only phone / guests). The form stays a
//  normal PATCH post (novalidate); JS only blocks the submit while a field is invalid.
function initModal(modal) {
    const form = modal.querySelector("form");
    const editButton = modal.querySelector("[data-booking-edit]");
    const saveButton = modal.querySelector("[data-booking-save]");
    const editable = [...modal.querySelectorAll("[data-editable]")];
    const urlTemplate = modal.dataset.updateUrlTemplate;
    const lockDays = Number(modal.dataset.locationLockDays) || 0;
    const banner = modal.querySelector("[data-booking-error]");

    const field = (name) => form.querySelector(`[name="${name}"]`);
    const errorFor = (name) =>
        form.querySelector(`[data-field-error="${name}"]`);

    const locationInput = field("event_location");
    const lockNote = modal.querySelector("[data-location-lock]");
    const typeSelect = field("event_type");
    const typeOther = field("event_type_other");
    const typeDisplay = modal.querySelector("[data-type-display]");
    const typeSelectWrap = modal.querySelector("[data-type-select-wrap]");
    const typeOtherWrap = modal.querySelector("[data-type-other-wrap]");
    const typeLabel = modal.querySelector("[data-type-label]");
    const othersTag = modal.querySelector("[data-others-tag]");
    const typeChange = modal.querySelector("[data-type-change]");

    let current = null;
    let editing = false;
    let locationLocked = false;

    // Event Location locks from `location_lock_days` before the start date (config/scheduling.php).
    // The backend must refuse the change as well; this only greys the field out.
    function isLocationLocked(booking) {
        const start = booking?.event?.start_date;
        if (!start || !lockDays) return false;
        const [year, month, day] = start.split("-").map(Number);
        const lockFrom = new Date(year, month - 1, day - lockDays);
        const now = new Date();
        const today = new Date(
            now.getFullYear(),
            now.getMonth(),
            now.getDate(),
        );
        return today >= lockFrom;
    }

    // "Edited" label + the client's original value, from booking.edited = { path: original }.
    function renderEdited(booking) {
        const edited = booking.edited ?? {};
        modal.querySelectorAll("[data-edited-note]").forEach((note) => {
            const path = note.dataset.editedNote;
            const has = Object.prototype.hasOwnProperty.call(edited, path);
            note.hidden = !has;
            if (has) {
                note.querySelector("[data-edited-original]").textContent =
                    isEmpty(edited[path]) ? "--" : edited[path];
            }
        });
    }

    // Event type: text while viewing; dropdown while editing; "Others" turns the dropdown into a
    // text field in the same spot and tags the label.
    function syncType() {
        const isOthers = typeSelect.value === "Others";
        const wasOthers = current?.event?.type_value === "Others";

        typeDisplay.hidden = editing;
        typeSelectWrap.hidden = !editing || isOthers;
        typeOtherWrap.hidden = !editing || !isOthers;
        othersTag.hidden = !(editing ? isOthers : wasOthers);
        // The dropdown moves its id onto its visible button, so the label can point at it.
        typeLabel.htmlFor = !editing
            ? typeDisplay.id
            : isOthers
              ? typeOther.id
              : "bm-event-type-select";
    }

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

        locationLocked = isLocationLocked(booking);
        lockNote.hidden = !(locationLocked && booking.editable !== false);
        locationInput.classList.toggle("is-locked", locationLocked);
        renderEdited(booking);
        syncType();
    }

    // ---- Errors -------------------------------------------------------------------------------
    function clearErrors() {
        form.querySelectorAll("[data-field-error]").forEach((el) => {
            el.textContent = "";
            el.classList.add("d-none");
        });
        form.querySelectorAll(".is-invalid").forEach((el) =>
            el.classList.remove("is-invalid"),
        );
        showBanner("");
    }

    function showBanner(message) {
        banner.textContent = message;
        banner.hidden = !message;
    }

    // Same rules and messages as the user side. Returns [element, message, error key] per field.
    function validate() {
        const value = (name) => field(name).value;
        const isOthers = typeSelect.value === "Others";
        const contact = field("venue_contact_person");
        const venueType = field("venue_type");

        return [
            [
                field("client_name"),
                getNameError(value("client_name"), "Client name"),
            ],
            [field("client_email"), getEmailError(value("client_email"))],
            [
                field("client_phone"),
                getContactNumberError(value("client_phone")),
            ],
            [
                field("client_address"),
                getRequiredError(value("client_address"), "Address"),
            ],
            [
                field("event_name"),
                getRequiredError(value("event_name"), "Event name"),
            ],
            [typeSelect, typeSelect.value ? "" : "Select an event type."],
            [
                typeOther,
                isOthers ? getRequiredError(typeOther.value, "Event type") : "",
            ],
            [
                locationInput,
                locationLocked
                    ? ""
                    : getRequiredError(locationInput.value, "Event location"),
            ],
            // Optional: only checked as a phone number if something was typed.
            [
                contact,
                isContactNumberUntouched(contact.value)
                    ? ""
                    : getContactNumberError(contact.value),
            ],
            [venueType, venueType.value ? "" : "Select a venue type."],
        ];
    }

    function setEditing(on) {
        const wasEditing = editing;
        editing = on;

        modal.classList.toggle("is-editing", on);
        editable.forEach((el) => {
            if (el instanceof HTMLSelectElement) el.disabled = !on;
            else el.readOnly = !on;
        });
        // A locked location stays read-only even in edit mode (it still posts its current value).
        if (locationLocked) locationInput.readOnly = true;

        saveButton.hidden = !on;
        // Label and icon swap in place (the button holds an icon, so textContent would wipe it).
        const label = editButton.querySelector("[data-booking-edit-label]");
        const icon = editButton.querySelector("[data-booking-edit-icon]");
        if (label) label.textContent = on ? "Cancel" : "Edit";
        if (icon) icon.className = `ph ${on ? "ph-x" : "ph-pencil-simple"}`;

        if (on) editable[0]?.focus();
        else clearErrors();
        // Cancelling an edit puts the original values back.
        if (!on && wasEditing && current) fill(current);
        syncType();
    }

    // ---- Live feedback + input filters (same wiring as the user-side steps) -------------------
    // The helpers check on blur / input; the gate keeps them quiet while the modal is read-only.
    const gate = (check) => (value) => (editing ? check(value) : "");
    wireLiveField(
        form,
        "client_name",
        gate((v) => getNameError(v, "Client name")),
    );
    wireLiveFieldImmediate(form, "client_email", gate(getEmailError));
    wireLiveFieldImmediate(form, "client_phone", gate(getContactNumberError));
    wireLiveField(
        form,
        "client_address",
        gate((v) => getRequiredError(v, "Address")),
    );
    wireLiveField(
        form,
        "event_name",
        gate((v) => getRequiredError(v, "Event name")),
    );
    wireLiveField(
        form,
        "event_location",
        gate((v) =>
            locationLocked ? "" : getRequiredError(v, "Event location"),
        ),
    );
    wireLiveFieldImmediate(
        form,
        "venue_contact_person",
        gate(getContactNumberError),
        {
            allowEmpty: true,
            isEmpty: isContactNumberUntouched,
        },
    );
    wireLiveField(
        form,
        "event_type_other",
        gate((v) => getRequiredError(v, "Event type")),
    );

    const filterInput = (name, pattern) =>
        field(name).addEventListener("input", (event) => {
            event.target.value = event.target.value.replace(pattern, "");
        });
    filterInput("client_name", /[^A-Za-z\s]/g); // letters only, like First/Last Name
    filterInput("client_phone", /\D/g);
    filterInput("venue_contact_person", /\D/g);
    filterInput("guest_count", /\D/g);

    // Plain class toggles on the native <select>s (the styled dropdown fires `change` on them).
    typeSelect.addEventListener("change", () => {
        setFieldState(typeSelect, errorFor("event_type"), "");
        syncType();
        if (typeSelect.value === "Others") typeOther.focus();
    });
    field("venue_type").addEventListener("change", (event) =>
        setFieldState(event.target, errorFor("venue_type"), ""),
    );
    typeChange.addEventListener("click", () => {
        typeSelect.value = ""; // goes through the dropdown, so its button updates too
        typeOther.value = "";
        setFieldState(typeOther, errorFor("event_type_other"), "");
        syncType();
        typeSelect.focus();
    });

    // is-invalid -> aria-invalid, for screen readers.
    new MutationObserver((records) =>
        records.forEach(({ target }) => {
            if (target.matches("input, select"))
                target.setAttribute(
                    "aria-invalid",
                    String(target.classList.contains("is-invalid")),
                );
        }),
    ).observe(form, {
        attributes: true,
        attributeFilter: ["class"],
        subtree: true,
    });

    form.addEventListener("input", () => showBanner(""));

    // ---- Modal events -------------------------------------------------------------------------
    modal.addEventListener("show.bs.modal", (event) => {
        const trigger = event.relatedTarget;
        if (!trigger?.dataset.booking) return;

        current = JSON.parse(trigger.dataset.booking);
        setEditing(false);
        fill(current);

        // History is a record, so there is nothing to edit there.
        editButton.hidden = current.editable === false;
    });

    modal.addEventListener("hidden.bs.modal", () => setEditing(false));

    editButton.addEventListener("click", () => setEditing(!editing));

    // Enter inside a read-only field must not submit the form; an edit with a bad field must not either.
    form.addEventListener("submit", (event) => {
        if (!editing) {
            event.preventDefault();
            return;
        }

        form.querySelectorAll('input[type="text"]').forEach((input) => {
            input.value = input.value.trim();
        });
        if (typeSelect.value !== "Others") typeOther.value = ""; // no stale "Others" text
        // Stored numbers may carry spaces ("0917 000 0001"); post plain digits like the user side.
        ["client_phone", "venue_contact_person"].forEach((name) => {
            field(name).value = field(name).value.replace(/\D/g, "");
        });

        const checks = validate();
        checks.forEach(([el, message]) =>
            setFieldState(el, errorFor(el.name), message),
        );
        const firstFailure = checks.find(([, message]) => message);
        if (firstFailure) {
            event.preventDefault();
            showBanner("Please fix the highlighted fields, then save again.");
            firstFailure[0].focus();
        }
    });

    // After a failed save the backend redirects back with errors; restoreServerErrors (below)
    // opens the booking and fires this so the admin lands in edit mode with the messages shown.
    modal.addEventListener("booking:restore", ({ detail }) => {
        setEditing(true);
        Object.entries(detail.old).forEach(([name, value]) => {
            const el = field(name);
            if (!el || (el === locationInput && locationLocked)) return;
            if (el.type !== "hidden" && el.name !== "_token")
                el.value = value ?? "";
        });
        syncType();

        const errors = Object.entries(detail.errors);
        errors.forEach(([name, messages]) => {
            const el = field(name);
            if (el) setFieldState(el, errorFor(name), messages[0]);
        });
        if (errors.length)
            showBanner(
                "Some details could not be saved. Please check the highlighted fields.",
            );
    });
}

// Backend contract (docs/backend-impact.md, section 16): on a rejected save, redirect back with
// the errors and old input and flash `edit_booking_id`. The modal then reopens on that booking.
function restoreServerErrors(modal) {
    const bookingId = modal.dataset.serverBooking;
    const errors = JSON.parse(modal.dataset.serverErrors || "{}");
    if (!bookingId || !Object.keys(errors).length) return;

    const row = document.querySelector(
        `[data-booking-row][data-booking-id="${bookingId}"]`,
    );
    const trigger = row?.querySelector('[data-bs-toggle="modal"]');
    if (!trigger) return;

    modal.addEventListener(
        "shown.bs.modal",
        () =>
            modal.dispatchEvent(
                new CustomEvent("booking:restore", {
                    detail: {
                        errors,
                        old: JSON.parse(modal.dataset.serverOld || "{}"),
                    },
                }),
            ),
        { once: true },
    );
    trigger.click();
}

//  Confirm destructive actions (Decline / Cancel)
function initConfirm() {
    document.addEventListener("submit", (event) => {
        const message = event.target.dataset?.confirm;
        if (message && !window.confirm(message)) event.preventDefault();
    });
}

function init() {
    // The modal listener must exist before initList runs: the ?booking= deep link clicks a row's
    // View Details button on load, and the modal fills in its show.bs.modal handler.
    const modal = document.getElementById("bookingModal");
    if (modal) initModal(modal);

    const body = document.querySelector("[data-bookings-body]");
    if (body) initList(body);
    if (modal) restoreServerErrors(modal); // after the list, so the row's button exists

    // After initList, so chips reflect any ?status= / ?package= deep link.
    document.querySelectorAll("[data-filter-bar]").forEach(initFilterSheet);

    initConfirm();
}

if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", init);
else init();
