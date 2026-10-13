
import { getCsrfToken } from "../auth/csrf.js";
import {
    getEmailError,
    getNameError,
    getPasswordError,
    getMatchError,
    getRequiredError,
    getContactNumberError,
    normalizeContactNumberInput,
    wireLiveField,
    setFieldState,
} from "../auth/validation.js";

const FORM_ID = "admin-account-form";
const NAME_FIELDS = ["first_name", "last_name", "middle_initial"];
const PASSWORD_FIELDS = ["password", "password_confirmation"];
const ROW_FIELDS = {
    name: NAME_FIELDS,
    email: ["email"],
    phone: ["phone"],
    password: PASSWORD_FIELDS,
};
const AVATAR_TYPES = ["image/jpeg", "image/png", "image/webp"];

// One test per line of the checklist; together they equal getPasswordError() === "".
const RULES = {
    length: (v) => v.length >= 8,
    lower: (v) => /[a-z]/.test(v),
    upper: (v) => /[A-Z]/.test(v),
    number: (v) => /\d/.test(v),
    special: (v) => /[^A-Za-z0-9]/.test(v),
};

const formatPhone = (digits) =>
    /^\d{11}$/.test(digits) ? digits.replace(/^(\d{4})(\d{3})(\d{4})$/, "$1 $2 $3") : digits;

function initAdminAccount() {
    const form = document.getElementById(FORM_ID);
    const root = document.querySelector("[data-admin-account]");
    if (!form || !root) return;

    const saveBtn = root.querySelector("[data-account-save]");
    const statusEl = root.querySelector("[data-account-status]");
    const confirmBox = root.querySelector("[data-account-confirm]");
    const avatarBox = root.querySelector("[data-avatar]");
    const avatarInput = root.querySelector("[data-avatar-input]");
    const avatarPick = root.querySelector("[data-avatar-pick]");
    const maxAvatarMb = Number(avatarInput.dataset.maxMb) || 2;

    const field = (name) => form.querySelector(`[name="${name}"]`);
    const errorEl = (name) => form.querySelector(`[data-field-error="${name}"]`);
    const rows = Object.fromEntries(
        [...root.querySelectorAll("[data-row]")].map((row) => [row.dataset.row, row]),
    );
    const isEditing = (key) => rows[key].classList.contains("is-editing");

    // Values as last saved (what Cancel restores). Password fields always start empty.
    const original = {};
    [...NAME_FIELDS, "email", "phone"].forEach((name) => (original[name] = field(name).value));

    let avatarFile = null;
    let avatarUrl = null;
    let saving = false;

    // ---------- validation ----------
    const validators = {
        first_name: (v) => getNameError(v, "First name"),
        last_name: (v) => getNameError(v, "Last name"),
        middle_initial: (v) =>
            v && !/^[A-Za-z]$/.test(v.trim()) ? "Middle initial must be a single letter." : "",
        email: getEmailError,
        phone: getContactNumberError,
        current_password: (v) => getRequiredError(v, "Current password"),
        password: (v) => {
            // Nothing typed in either password box: the row is open but not being used yet.
            if (!v && !field("password_confirmation").value) return "";
            const message = getPasswordError(v);
            if (message) return message;
            return v === field("current_password").value
                ? "Choose a password different from your current one."
                : "";
        },
        password_confirmation: (v) =>
            !v && !field("password").value ? "" : getMatchError(v, field("password").value),
    };
    const errorFor = (name) => validators[name](field(name).value);

    // ---------- what changed ----------
    const passwordInUse = () => PASSWORD_FIELDS.some((name) => field(name).value !== "");
    const changed = (name) => field(name).value.trim() !== original[name].trim();

    /** Names of the fields that will be validated and sent. */
    function activeFields() {
        const names = [];
        if (isEditing("name") && NAME_FIELDS.some(changed)) names.push(...NAME_FIELDS);
        if (isEditing("email") && changed("email")) names.push("email");
        if (isEditing("phone") && changed("phone")) names.push("phone");
        if (isEditing("password") && passwordInUse()) names.push(...PASSWORD_FIELDS);
        return names;
    }
    const needsCurrentPassword = () => {
        const names = activeFields();
        return names.includes("email") || names.includes("password");
    };
    const isDirty = () => activeFields().length > 0 || avatarFile !== null;
    const problems = () => {
        const names = activeFields();
        if (needsCurrentPassword()) names.push("current_password");
        return names.filter((name) => errorFor(name));
    };

    // ---------- UI sync ----------
    function syncRules() {
        const value = field("password").value;
        root.querySelectorAll("[data-password-rule]").forEach((item) => {
            const met = RULES[item.dataset.passwordRule](value);
            item.classList.toggle("is-met", met);
            item.querySelector("i").className = `ph ph-${met ? "check-circle" : "circle"}`;
        });
    }

    function syncUi() {
        const needs = needsCurrentPassword();
        confirmBox.hidden = !needs;
        if (!needs) {
            field("current_password").value = "";
            setFieldState(field("current_password"), errorEl("current_password"), "");
        }
        saveBtn.disabled = saving || !isDirty() || problems().length > 0;
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
        row.querySelector("[data-row-edit]").setAttribute("aria-expanded", "true");
        row.querySelector("[data-row-panel] input")?.focus();
        syncUi();
    }

    function closeRow(key, { reset = true, focusBack = false } = {}) {
        const row = rows[key];
        if (reset) {
            ROW_FIELDS[key].forEach((name) => {
                field(name).value = original[name] ?? "";
                setFieldState(field(name), errorEl(name), "");
            });
        }
        row.classList.remove("is-editing");
        row.querySelector("[data-row-view]").hidden = false;
        row.querySelector("[data-row-panel]").hidden = true;
        row.querySelector("[data-row-edit]").setAttribute("aria-expanded", "false");
        if (key === "password") syncRules();
        syncUi();
        if (focusBack) row.querySelector("[data-row-edit]").focus();
    }

    Object.keys(rows).forEach((key) => {
        const row = rows[key];
        row.querySelector("[data-row-edit]").addEventListener("click", () => openRow(key));
        row.querySelector("[data-row-cancel]").addEventListener("click", () =>
            closeRow(key, { focusBack: true }),
        );
        row.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && isEditing(key)) closeRow(key, { focusBack: true });
        });
    });

    // ---------- photo ----------
    function showAvatar(src) {
        const img = document.createElement("img");
        img.src = src;
        img.alt = "Your profile photo";
        avatarBox.replaceChildren(img);
    }

    avatarPick.addEventListener("click", () => avatarInput.click());
    avatarInput.addEventListener("change", () => {
        const file = avatarInput.files[0];
        if (!file) return;

        let message = "";
        if (!AVATAR_TYPES.includes(file.type)) message = "Use a JPG, PNG or WebP image.";
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

    // ---------- password show / hide ----------
    form.querySelectorAll("[data-password-toggle]").forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        button.addEventListener("click", () => {
            const show = input.type === "password";
            input.type = show ? "text" : "password";
            button.setAttribute("aria-pressed", String(show));
            button.setAttribute("aria-label", show ? "Hide password" : "Show password");
            button.querySelector("i").className = `ph ph-${show ? "eye-slash" : "eye"}`;
        });
    });

    // ---------- live feedback ----------
    Object.keys(validators).forEach((name) => wireLiveField(form, name, validators[name]));

    form.addEventListener("input", (event) => {
        const target = event.target;
        if (target.name === "phone") target.value = normalizeContactNumberInput(target.value);
        if (target.name === "middle_initial")
            target.value = target.value.toUpperCase().replace(/[^A-Z]/g, "");

        // A message already on screen follows the value (the match message depends on the other box).
        PASSWORD_FIELDS.forEach((name) => {
            if (!errorEl(name).classList.contains("d-none"))
                setFieldState(field(name), errorEl(name), errorFor(name));
        });
        clearStatus();
        syncRules();
        syncUi();
    });

    // ---------- save ----------
    function applySaved(names, saved) {
        names.forEach((name) => {
            if (name === "current_password" || PASSWORD_FIELDS.includes(name)) return;
            original[name] = String(saved[name] ?? field(name).value).trim();
            field(name).value = original[name];
        });
        [...PASSWORD_FIELDS, "current_password"].forEach((name) => {
            field(name).value = "";
            setFieldState(field(name), errorEl(name), "");
        });

        const initial = original.middle_initial ? `${original.middle_initial}. ` : "";
        const text = (key, value) => {
            rows[key].querySelector("[data-row-value]").textContent = value;
        };
        text("name", `${original.first_name} ${initial}${original.last_name}`.trim());
        text("email", original.email);
        text("phone", formatPhone(original.phone));

        if (saved.avatar_url) showAvatar(saved.avatar_url);
        else if (!avatarBox.querySelector("img"))
            avatarBox.textContent = (
                original.first_name.charAt(0) + original.last_name.charAt(0)
            ).toUpperCase();
        avatarFile = null;
        avatarInput.value = "";

        Object.keys(rows).forEach((key) => {
            if (isEditing(key)) closeRow(key, { reset: false });
        });
        syncRules();
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearStatus();

        const failing = problems();
        if (failing.length) {
            failing.forEach((name) => setFieldState(field(name), errorEl(name), errorFor(name)));
            field(failing[0]).focus();
            return;
        }
        if (!isDirty()) return;

        if (!form.getAttribute("action")) {
            showStatus("Saving isn't connected yet, so nothing was changed.", "error");
            return;
        }

        const names = activeFields();
        if (needsCurrentPassword()) names.push("current_password");

        const body = new FormData();
        body.append("_method", "PATCH"); // multipart/form-data is only parsed on POST
        names.forEach((name) => {
            const value = field(name).value;
            body.append(name, name.includes("password") ? value : value.trim());
        });
        if (avatarFile) body.append("avatar", avatarFile);

        saving = true;
        syncUi();

        try {
            const response = await fetch(form.action, {
                method: "POST",
                headers: { Accept: "application/json", "X-CSRF-TOKEN": getCsrfToken(form) },
                body,
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                Object.entries(data.errors ?? {}).forEach(([name, messages]) => {
                    const input = field(name);
                    if (input) setFieldState(input, errorEl(name), messages[0]);
                    else if (name === "avatar") {
                        errorEl("avatar").textContent = messages[0];
                        errorEl("avatar").classList.remove("d-none");
                    }
                });
                showStatus(
                    data.message || "We couldn't save your changes. Please review the fields and try again.",
                    "error",
                );
                return;
            }

            applySaved(names, data.admin ?? {});
            showStatus(data.message || "Your changes have been saved.", "success");
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

    syncRules();
    syncUi();
}

document.addEventListener("DOMContentLoaded", initAdminAccount);