// Admin Packages page:
// one shared modal in three modes (view / edit / create), Price formatting,
// small client-side checks, and reopening after a server validation error.
// Bootstrap's modal is used through its data attributes (admin.js already loads Bootstrap).
// The confirm() prompt on "Unavailable" is the global [data-confirm] listener in bookings.js.

const money = (value) => `Php ${Number(value).toLocaleString("en-US")}`;
const digitsOnly = (value) => String(value ?? "").replace(/\D/g, "");
const EMPTY = { id: null, name: "", price: null, features: [] };

function setStatus(message, isError = false) {
    const status = document.querySelector("[data-package-status]");
    if (!status) return;

    status.textContent = message;
    status.classList.toggle("admin-alert--danger", isError);
    status.hidden = !message;
}

async function sendJson(url, method, csrfToken, payload) {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify(payload),
    });

    let body;
    try {
        body = await response.json();
    } catch {
        throw new Error("The server returned an invalid JSON response.");
    }

    return { response, body };
}

function initModal(modal) {
    const form = modal.querySelector("[data-package-form]");
    const title = modal.querySelector("[data-package-title]");
    const editLink = modal.querySelector("[data-package-edit]");
    const backButton = modal.querySelector("[data-package-back]");
    const cancelButton = modal.querySelector("[data-package-cancel]");
    const saveButton = modal.querySelector("[data-package-save]");
    const nameInput = modal.querySelector('[data-package-field="name"]');
    const priceInput = modal.querySelector("[data-package-price]"); // visible "Php 5,000"
    const priceValue = modal.querySelector('[data-package-field="price"]'); // submitted digits
    const featuresInput = modal.querySelector(
        '[data-package-field="features"]',
    );
    const featuresView = modal.querySelector("[data-package-features-view]");
    const featuresHint = modal.querySelector("[data-package-hint]");
    const methodInput = modal.querySelector("[data-package-method]");
    const modeInput = modal.querySelector("[data-package-mode]");
    const idInput = modal.querySelector("[data-package-id]");
    const editable = [...modal.querySelectorAll("[data-editable]")];
    const errors = [...modal.querySelectorAll("[data-package-error]")];

    let current = EMPTY;
    let mode = "view";
    let pendingOld = null; // old input after a failed server validation

    // Fill / mode
    function fill(pkg) {
        nameInput.value = pkg.name ?? "";
        priceValue.value = pkg.price ?? "";
        priceInput.value = pkg.price ? money(pkg.price) : "";
        featuresInput.value = (pkg.features ?? []).join("\n");
        featuresView.replaceChildren(
            ...(pkg.features ?? []).map((text) =>
                Object.assign(document.createElement("li"), {
                    textContent: text,
                }),
            ),
        );
    }

    function setMode(next) {
        mode = next;
        const editing = next !== "view";

        modal.classList.toggle("is-editing", editing);
        editable.forEach((el) => {
            el.readOnly = !editing;
        });
        featuresView.hidden = editing;
        featuresInput.hidden = !editing;
        featuresHint.hidden = !editing;
        editLink.hidden = editing;
        backButton.hidden = editing;
        cancelButton.hidden = !editing;
        saveButton.hidden = !editing;
        title.textContent =
            next === "create" ? "New Package" : "Package Information";

        // Create posts to the store route; edit PATCHes the update route.
        methodInput.disabled = next === "create";
        form.action =
            next === "create"
                ? modal.dataset.storeUrl
                : modal.dataset.updateUrlTemplate.replace(
                      "__ID__",
                      encodeURIComponent(current.id ?? ""),
                  );
        modeInput.value = next;
        idInput.value = current.id ?? "";

        // In create mode Cancel just closes the modal; otherwise it returns to view mode.
        if (next === "create")
            cancelButton.setAttribute("data-bs-dismiss", "modal");
        else cancelButton.removeAttribute("data-bs-dismiss");
    }

    // Errors
    function showError(field, message) {
        const box = modal.querySelector(`[data-package-error="${field}"]`);
        const control = modal.querySelector(`[data-package-field="${field}"]`);
        const visible = field === "price" ? priceInput : control;
        box.textContent = message;
        box.hidden = false;
        visible.setAttribute("aria-invalid", "true");
        visible.setAttribute("aria-describedby", box.id);
    }

    function clearErrors() {
        errors.forEach((box) => {
            box.textContent = "";
            box.hidden = true;
        });
        [nameInput, priceInput, featuresInput].forEach((el) => {
            el.removeAttribute("aria-invalid");
            if (el !== featuresInput) el.removeAttribute("aria-describedby");
        });
        featuresInput.setAttribute("aria-describedby", "pk-features-hint");
    }

    // Server errors are already printed by Blade; mark their fields invalid.
    function markServerErrors() {
        errors
            .filter((box) => box.textContent.trim())
            .forEach((box) => {
                showError(box.dataset.packageError, box.textContent.trim());
            });
    }

    // Price: digits while typing, "Php 5,000" when the box is left
    priceInput.addEventListener("focus", () => {
        if (mode !== "view") priceInput.value = priceValue.value;
    });
    priceInput.addEventListener("input", () => {
        priceValue.value = digitsOnly(priceInput.value);
    });
    priceInput.addEventListener("blur", () => {
        if (mode !== "view")
            priceInput.value = priceValue.value ? money(priceValue.value) : "";
    });

    // Events
    modal.addEventListener("show.bs.modal", (event) => {
        const trigger = event.relatedTarget;
        current = trigger?.dataset.package
            ? JSON.parse(trigger.dataset.package)
            : EMPTY;
        const isNew = current === EMPTY;

        fill(current);

        if (pendingOld) {
            // Reopened after the server rejected the form: put the typed values back.
            nameInput.value = pendingOld.name ?? "";
            priceValue.value = digitsOnly(pendingOld.price);
            priceInput.value = priceValue.value ? money(priceValue.value) : "";
            featuresInput.value = pendingOld.features ?? "";
            setMode(isNew ? "create" : "edit");
            markServerErrors();
            pendingOld = null;
            return;
        }

        clearErrors();
        setMode(isNew ? "create" : "view");
    });

    modal.addEventListener("shown.bs.modal", () => {
        if (mode !== "view") nameInput.focus();
    });

    editLink.addEventListener("click", () => {
        setMode("edit");
        nameInput.focus();
    });

    // Cancel (edit mode of an existing package): restore the original values, back to view.
    cancelButton.addEventListener("click", () => {
        if (mode === "create") return; // data-bs-dismiss closes it
        clearErrors();
        fill(current);
        setMode("view");
    });

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (mode === "view") {
            return;
        }

        clearErrors();

        // One feature per line; drop blank lines and any bullet the admin typed.
        featuresInput.value = featuresInput.value
            .split("\n")
            .map((line) => line.replace(/^[\s•*-]+/, "").trim())
            .filter(Boolean)
            .join("\n");

        const problems = [];
        if (!nameInput.value.trim())
            problems.push(["name", "Enter a package name."]);
        if (!priceValue.value || Number(priceValue.value) < 1)
            problems.push(["price", "Enter a price greater than 0."]);
        if (!featuresInput.value)
            problems.push([
                "features",
                "Add at least one feature (one per line).",
            ]);

        if (problems.length) {
            problems.forEach(([field, message]) => showError(field, message));
            const [first] = problems[0];
            (first === "price"
                ? priceInput
                : modal.querySelector(`[data-package-field="${first}"]`)
            ).focus();
            return;
        }

        saveButton.disabled = true;
        try {
            const method = mode === "create" ? "POST" : "PATCH";
            const { response, body } = await sendJson(
                form.action,
                method,
                form.querySelector('[name="_token"]').value,
                {
                    name: nameInput.value.trim(),
                    price: priceValue.value,
                    features: featuresInput.value,
                },
            );

            if (response.status === 422 && body.errors) {
                Object.entries(body.errors).forEach(([field, messages]) => {
                    if (modal.querySelector(`[data-package-error="${field}"]`))
                        showError(field, messages[0]);
                });
                return;
            }

            if (!response.ok)
                throw new Error(body.message || "Unable to save this package.");

            setStatus(body.message || "Package saved successfully.");
            window.setTimeout(() => window.location.reload(), 650);
        } catch (error) {
            setStatus(error.message || "Unable to save this package.", true);
        } finally {
            saveButton.disabled = false;
        }
    });

    // Server-side validation failed: reopen via the trigger that opened it
    if (modal.dataset.reopen) {
        const { mode: oldMode, id, old } = JSON.parse(modal.dataset.reopen);
        const trigger =
            oldMode === "create" || !id
                ? document.querySelector(".admin-packages__add")
                : [...document.querySelectorAll("[data-package]")].find(
                      (el) => JSON.parse(el.dataset.package).id === id,
                  );

        if (trigger) {
            pendingOld = old;
            trigger.click(); // Bootstrap's data-api opens the modal and sets relatedTarget
        }
    }
}

function initAvailabilityForms() {
    document
        .querySelectorAll("[data-package-card] form")
        .forEach((form) => {
            form.addEventListener("submit", async (event) => {
                event.preventDefault();
                const message = form.dataset.confirm;
                if (message && !window.confirm(message)) return;

                const button = form.querySelector('button[type="submit"]');
                button.disabled = true;
                try {
                    const { response, body } = await sendJson(
                        form.action,
                        "PATCH",
                        form.querySelector('[name="_token"]').value,
                        { availability: form.querySelector('[name="availability"]').value },
                    );

                    if (!response.ok)
                        throw new Error(
                            body.message || "Unable to update package availability.",
                        );

                    const card = form.closest("[data-package-card]");
                    card.classList.toggle(
                        "is-unavailable",
                        !body.data.available,
                    );
                    card.querySelectorAll("form").forEach((stateForm) => {
                        const active =
                            (stateForm.querySelector('[name="availability"]').value ===
                                "available") === body.data.available;
                        const stateButton = stateForm.querySelector("button");
                        stateButton.classList.toggle(
                            "admin-pill--solid",
                            active,
                        );
                        stateButton.setAttribute("aria-pressed", String(active));
                    });
                    setStatus(body.message);
                } catch (error) {
                    setStatus(
                        error.message ||
                            "Unable to update package availability.",
                        true,
                    );
                } finally {
                    button.disabled = false;
                }
            });
        });
}

function init() {
    const modal = document.getElementById("packageModal");
    if (modal) initModal(modal);
    initAvailabilityForms();
}

if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", init);
else init();
