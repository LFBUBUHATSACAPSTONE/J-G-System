// Admin account page. The behaviour is the shared profile card (js/account/profile-core.js) and the
// rules are the shared ones (js/account/profile-rules.js); this file only says which fields exist and
// how this page talks to the server (unchanged: multipart POST with _method=PATCH, only the changed
// fields, answer under `admin`).
import { initProfileForm } from "../account/profile-core.js";
import {
    rules,
    passwordRules,
    filters,
    formatPhone,
} from "../account/profile-rules.js";

function initAdminAccount() {
    const form = document.getElementById("admin-account-form");
    if (!form) return;
    const get = (name) => form.querySelector(`[name="${name}"]`)?.value ?? "";

    initProfileForm({
        formId: "admin-account-form",
        transport: "multipart",
        responseKey: "admin",
        rows: {
            name: ["first_name", "middle_name", "last_name"],
            email: ["email"],
            phone: ["phone"],
            password: ["password", "password_confirmation"],
        },
        immediate: ["first_name", "middle_name", "last_name", "email", "phone"],
        guarded: ["email", "password"], // these need the current password
        validators: {
            first_name: rules.first_name,
            last_name: rules.last_name,
            middle_name: rules.middle_name,
            email: rules.email,
            phone: rules.phone,
            current_password: rules.current_password,
            ...passwordRules(get),
        },
        filters: {
            first_name: filters.first_name,
            last_name: filters.last_name,
            middle_name: filters.middle_name,
            phone: filters.phone,
        },
        display: {
            name: (v) =>
                [v.first_name, v.middle_name, v.last_name]
                    .filter(Boolean)
                    .join(" "),
            email: (v) => v.email,
            phone: (v) => formatPhone(v.phone),
        },
    });
}

document.addEventListener("DOMContentLoaded", initAdminAccount);
