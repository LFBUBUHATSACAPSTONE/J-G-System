/**
 * Shared behaviour for the profile card used by the admin account page and the customer Profile page
 * (markup: resources/views/components/profile/*, styles: resources/sass/profile/_profile.scss).
 *
 * What it does: rows that open an inline editor (Edit / Change / Cancel), live validation, a Save button
 * that stays disabled until something valid has changed, an optional profile photo, an optional
 * "current password" box for guarded changes, and the fetch() save with 422 handling.
 *
 * What it does NOT do: decide routes, field names or response shapes. Each page passes those in, so
 * the requests are exactly the ones the pages sent before this file existed:
 *   - transport "multipart": POST + `_method=PATCH`, only the fields that changed (+ avatar)   [admin]
 *   - transport "json":      PATCH with a JSON body of `jsonFields`, always all of them        [customer]
 *
 * Markup hooks: [data-profile-root], [data-row], [data-row-view|value|edit|panel|cancel],
 * [data-profile-save], [data-profile-reset], [data-profile-status], [data-profile-confirm],
 * [data-field-error="<name>"], [data-avatar], [data-avatar-pick], [data-avatar-input].
 */
import { getCsrfToken } from "../auth/csrf.js";
import {
    getPasswordError,
    wireLiveField,
    wireLiveFieldImmediate,
    setFieldState,
} from "../auth/validation.js";
import { syncPasswordChecklist, wirePasswordToggles } from "./profile-rules.js";

const AVATAR_TYPES = ["image/jpeg", "image/png", "image/webp"];
const PASSWORD_FIELDS = ["password", "password_confirmation"];

/**
 * @param {object}   cfg
 * @param {string}   cfg.formId
 * @param {Object<string,string[]>} cfg.rows        row key -> its field names
 * @param {Object<string,Function>} cfg.validators  field -> (value) => message
 * @param {Object<string,Function>} [cfg.filters]   field -> (value) => cleaned value, applied while typing
 * @param {Object<string,Function>} [cfg.clean]     field -> (value) => value actually sent / stored
 * @param {Object<string,Function>} cfg.display     row key -> (saved values) => text shown in the grey row
 * @param {"multipart"|"json"} cfg.transport
 * @param {string[]} [cfg.jsonFields]               json transport: the fields always sent
 * @param {string}   [cfg.responseKey]              multipart: key of the saved values in the 200 body
 * @param {string[]} [cfg.immediate]               fields checked on every keystroke (the sign-up form's behaviour);
 *                                                  the others are checked on blur, then live
 * @param {string[]} [cfg.guarded]                  rows that need the current password when they change
 * @param {Function} [cfg.onSaved]                  (saved values) => void, after a successful save
 */
export function initProfileForm(cfg) {
    const form = document.getElementById(cfg.formId);
    const root = document.querySelector("[data-profile-root]");
    if (!form || !root) return;

    const saveBtn = root.querySelector("[data-profile-save]");
    const resetBtn = root.querySelector("[data-profile-reset]");
    const statusEl = root.querySelector("[data-profile-status]");
    const confirmBox = root.querySelector("[data-profile-confirm]");
    const avatarBox = root.querySelector("[data-avatar]");
    const avatarInput = root.querySelector("[data-avatar-input]");
    const avatarPick = root.querySelector("[data-avatar-pick]");
    const maxAvatarMb = Number(avatarInput?.dataset.maxMb) || 2;

    const field = (name) => form.querySelector(`[name="${name}"]`);
    const errorEl = (name) =>
        form.querySelector(`[data-field-error="${name}"]`);
    const rows = Object.fromEntries(
        [...root.querySelectorAll("[data-row]")]
            .filter((row) => row.querySelector("[data-row-edit]"))
            .map((row) => [row.dataset.row, row]),
    );
    const isEditing = (key) => rows[key].classList.contains("is-editing");
    const clean = (name, value) =>
        cfg.clean?.[name] ? cfg.clean[name](value) : value.trim();

    // Fields that exist on this page, and the ones that are plain values (not passwords).
    const rowKeys = Object.keys(rows);
    const known = (names) => names.filter((name) => field(name));
    const valueFields = rowKeys
        .flatMap((key) => known(cfg.rows[key] ?? []))
        .filter((name) => !PASSWORD_FIELDS.includes(name));
    const hasPassword = Boolean(field("password"));

    // Values as last saved (what Cancel restores). Password fields always start empty.
    const original = {};
    valueFields.forEach((name) => (original[name] = field(name).value));

    let avatarFile = null;
    let avatarUrl = null;
    let saving = false;

    const errorFor = (name) =>
        cfg.validators[name] ? cfg.validators[name](field(name).value) : "";

    /**
     * Shows a field's state. For the new password a rule failure only turns the box red: the requirement
     * list under it already says what is missing, so repeating it as text would be a second, identical
     * validation. Other messages (e.g. "different from your current one") still show as text.
     */
    function paintField(name) {
        const input = field(name);
        const message = errorFor(name);
        if (name === "password") {
            const ruleFailure =
                message && message === getPasswordError(input.value);
            setFieldState(input, errorEl(name), ruleFailure ? "" : message);
            input.classList.toggle("is-invalid", Boolean(message));
            return;
        }
        setFieldState(input, errorEl(name), message);
    }

    // ---------- what changed ----------
    const passwordInUse = () =>
        PASSWORD_FIELDS.some((name) => field(name)?.value);
    const changed = (name) =>
        field(name).value.trim() !== original[name].trim();

    /** Names of the fields that will be validated and sent. */
    function activeFields() {
        const names = [];
        rowKeys.forEach((key) => {
            if (!isEditing(key)) return;
            const fields = known(cfg.rows[key] ?? []);
            if (key === "password") {
                if (passwordInUse()) names.push(...fields);
            } else if (fields.some(changed)) {
                names.push(...fields);
            }
        });
        return names;
    }

    const needsCurrentPassword = () =>
        Boolean(confirmBox) &&
        activeFields().some((name) =>
            (cfg.guarded ?? []).some((key) =>
                known(cfg.rows[key] ?? []).includes(name),
            ),
        );
    const isDirty = () => activeFields().length > 0 || avatarFile !== null;
    const problems = () => {
        const names = activeFields();
        if (needsCurrentPassword()) names.push("current_password");
        return names.filter((name) => errorFor(name));
    };

    // ---------- UI sync ----------
    function syncUi() {
        if (confirmBox) {
            const needs = needsCurrentPassword();
            confirmBox.hidden = !needs;
            if (!needs) {
                field("current_password").value = "";
                setFieldState(
                    field("current_password"),
                    errorEl("current_password"),
                    "",
                );
            }
        }
        const dirty = isDirty();
        saveBtn.disabled = saving || !dirty || problems().length > 0;
        if (resetBtn) resetBtn.disabled = saving || !dirty;
    }

    function showStatus(message, type) {
        statusEl.textContent = message;
        statusEl.classList.toggle("is-error", type === "error");
        statusEl.classList.remove("d-none");
    }

    function clearStatus() {
        statusEl.textContent = "";
        statusEl.classList.add("d-none");
        statusEl.classList.remove("is-error");
    }

    // ---------- rows ----------
    function openRow(key) {
        const row = rows[key];
        row.classList.add("is-editing");
        row.querySelector("[data-row-view]").hidden = true;
        row.querySelector("[data-row-panel]").hidden = false;
        row.querySelector("[data-row-edit]").setAttribute(
            "aria-expanded",
            "true",
        );
        row.querySelector("[data-row-panel] input")?.focus();
        syncUi();
    }

    function closeRow(key, { reset = true, focusBack = false } = {}) {
        const row = rows[key];
        if (reset) {
            known(cfg.rows[key] ?? []).forEach((name) => {
                field(name).value = original[name] ?? "";
                setFieldState(field(name), errorEl(name), "");
            });
        }
        row.classList.remove("is-editing");
        row.querySelector("[data-row-view]").hidden = false;
        row.querySelector("[data-row-panel]").hidden = true;
        row.querySelector("[data-row-edit]").setAttribute(
            "aria-expanded",
            "false",
        );
        if (key === "password" && hasPassword)
            syncPasswordChecklist(root, field("password").value);
        syncUi();
        if (focusBack) row.querySelector("[data-row-edit]").focus();
    }

    rowKeys.forEach((key) => {
        const row = rows[key];
        row.querySelector("[data-row-edit]").addEventListener("click", () =>
            openRow(key),
        );
        row.querySelector("[data-row-cancel]").addEventListener("click", () =>
            closeRow(key, { focusBack: true }),
        );
        row.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && isEditing(key))
                closeRow(key, { focusBack: true });
        });
    });

    resetBtn?.addEventListener("click", () => {
        rowKeys.forEach((key) => isEditing(key) && closeRow(key));
        if (avatarFile) discardAvatar();
        clearStatus();
        syncUi();
    });

    // ---------- photo (admin only) ----------
    const initialsOf = (first, last) =>
        (first.charAt(0) + last.charAt(0)).toUpperCase();
    const savedAvatarMarkup = avatarBox ? avatarBox.innerHTML : "";

    function showAvatar(src) {
        const img = document.createElement("img");
        img.src = src;
        img.alt = "Your profile photo";
        avatarBox.replaceChildren(img);
    }

    function discardAvatar() {
        avatarFile = null;
        avatarInput.value = "";
        if (avatarUrl) URL.revokeObjectURL(avatarUrl);
        avatarUrl = null;
        avatarBox.innerHTML = savedAvatarMarkup;
    }

    if (avatarPick && avatarInput) {
        avatarPick.addEventListener("click", () => avatarInput.click());
        avatarInput.addEventListener("change", () => {
            const file = avatarInput.files[0];
            if (!file) return;

            let message = "";
            if (!AVATAR_TYPES.includes(file.type))
                message = "Use a JPG, PNG or WebP image.";
            else if (file.size > maxAvatarMb * 1024 * 1024)
                message = `The image must be ${maxAvatarMb} MB or smaller.`;

            const error = errorEl("avatar");
            error.textContent = message;
            error.classList.toggle("d-none", !message);
            if (message) {
                avatarInput.value = "";
                return;
            }

            avatarFile = file;
            if (avatarUrl) URL.revokeObjectURL(avatarUrl);
            avatarUrl = URL.createObjectURL(file);
            showAvatar(avatarUrl);
            clearStatus();
            syncUi();
        });
    }

    // ---------- password show / hide ----------
    wirePasswordToggles(form);

    // ---------- live feedback ----------
    // Same behaviour as the sign-up form: the input filters run first (so each live validator sees the
    // already-filtered value); the profile fields are checked on every keystroke, the password boxes
    // on blur and then live.
    Object.entries(cfg.filters ?? {}).forEach(([name, filter]) =>
        field(name)?.addEventListener("input", (event) => {
            const filtered = filter(event.target.value);
            if (filtered !== event.target.value) event.target.value = filtered;
        }),
    );

    const immediate = cfg.immediate ?? [];
    Object.keys(cfg.validators).forEach((name) => {
        if (!field(name) || name === "password") return;
        (immediate.includes(name) ? wireLiveFieldImmediate : wireLiveField)(
            form,
            name,
            cfg.validators[name],
        );
    });
    if (hasPassword) {
        // New password: same blur-then-live timing as wireLiveField, drawn by paintField().
        let passwordTouched = false;
        field("password").addEventListener("blur", () => {
            passwordTouched = true;
            paintField("password");
        });
        field("password").addEventListener("input", () => {
            if (passwordTouched) paintField("password");
        });
    }

    form.addEventListener("input", () => {
        // A message already on screen follows the value (the match message depends on the other box).
        PASSWORD_FIELDS.forEach((name) => {
            const el = errorEl(name);
            if (
                el &&
                (!el.classList.contains("d-none") ||
                    field(name).classList.contains("is-invalid"))
            )
                paintField(name);
        });
        clearStatus();
        if (hasPassword) syncPasswordChecklist(root, field("password").value);
        syncUi();
    });

    // ---------- save ----------
    function applySaved(sent, fromServer) {
        const saved = { ...sent, ...fromServer };
        known(Object.keys(sent)).forEach((name) => {
            if (PASSWORD_FIELDS.includes(name) || name === "current_password")
                return;
            original[name] = clean(
                name,
                String(saved[name] ?? sent[name] ?? ""),
            );
            field(name).value = original[name];
        });
        [...PASSWORD_FIELDS, "current_password"].forEach((name) => {
            if (!field(name)) return;
            field(name).value = "";
            setFieldState(field(name), errorEl(name), "");
        });

        // The grey rows show what was saved.
        rowKeys.forEach((key) => {
            const text = cfg.display[key]?.(original);
            const el = rows[key].querySelector("[data-row-value]");
            if (text === undefined || !el) return;
            el.textContent = text || el.dataset.empty || "";
            el.classList.toggle("is-empty", !text && Boolean(el.dataset.empty));
        });

        if (avatarBox) {
            if (saved.avatar_url) showAvatar(saved.avatar_url);
            else if (!avatarBox.querySelector("img"))
                avatarBox.textContent =
                    initialsOf(
                        original.first_name ?? "",
                        original.last_name ?? "",
                    ) || "A";
            avatarFile = null;
            if (avatarInput) avatarInput.value = "";
        }

        rowKeys.forEach(
            (key) => isEditing(key) && closeRow(key, { reset: false }),
        );
        if (hasPassword) syncPasswordChecklist(root, "");
        cfg.onSaved?.(original);
    }

    function buildRequest(names) {
        if (cfg.transport === "json") {
            const body = {};
            known(cfg.jsonFields).forEach(
                (name) => (body[name] = clean(name, field(name).value)),
            );
            return {
                sent: body,
                init: {
                    method: "PATCH",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": getCsrfToken(form),
                    },
                    body: JSON.stringify(body),
                },
            };
        }

        const body = new FormData();
        const sent = {};
        body.append("_method", "PATCH"); // multipart/form-data is only parsed on POST
        names.forEach((name) => {
            const value = field(name).value;
            const out = name.includes("password") ? value : clean(name, value);
            sent[name] = out;
            body.append(name, out);
        });
        if (avatarFile) body.append("avatar", avatarFile);
        return {
            sent,
            init: {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(form),
                },
                body,
            },
        };
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearStatus();

        const failing = problems();
        if (failing.length) {
            failing.forEach(paintField);
            field(failing[0]).focus();
            return;
        }
        if (!isDirty()) return;

        if (!form.getAttribute("action")) {
            showStatus(
                "Saving isn't connected yet, so nothing was changed.",
                "error",
            );
            return;
        }

        const names = activeFields();
        if (needsCurrentPassword()) names.push("current_password");
        const { sent, init } = buildRequest(names);

        saving = true;
        syncUi();

        try {
            const response = await fetch(form.action, init);
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                Object.entries(data.errors ?? {}).forEach(
                    ([name, messages]) => {
                        const input = field(name);
                        if (input)
                            setFieldState(input, errorEl(name), messages[0]);
                        else if (name === "avatar" && errorEl("avatar")) {
                            errorEl("avatar").textContent = messages[0];
                            errorEl("avatar").classList.remove("d-none");
                        }
                    },
                );
                showStatus(
                    data.message ||
                        "We couldn't save your changes. Please review the fields and try again.",
                    "error",
                );
                return;
            }

            // Multipart pages read the saved values from the response; the JSON page keeps what it sent.
            const fromServer = cfg.responseKey
                ? (data[cfg.responseKey] ?? {})
                : {};
            applySaved(sent, fromServer);
            showStatus(
                data.message || "Your changes have been saved.",
                "success",
            );
        } catch {
            showStatus(
                "Something went wrong on our end. Please check your connection and try again.",
                "error",
            );
        } finally {
            saving = false;
            syncUi();
        }
    });

    // ---------- unsaved changes ----------
    window.addEventListener("beforeunload", (event) => {
        if (saving || !isDirty()) return;
        event.preventDefault();
        event.returnValue = "";
    });

    if (hasPassword) syncPasswordChecklist(root, field("password").value);
    syncUi();
}
