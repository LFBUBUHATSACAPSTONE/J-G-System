/**
 * Account > Profile. The behaviour is the shared profile card (profile-core.js, the same one the admin
 * account page uses) and the rules are the shared ones (profile-rules.js). This file only says which
 * fields exist on the customer page and how it talks to the server.
 *
 * Back-end contract for `user.account.profile.update` (unchanged: PATCH, JSON body
 * {first_name, last_name, phone, address}, always all four; email is read-only and not sent):
 *   200 {"ok": true, "message": "…", "profile": {…}}  -> saved
 *   422 {"message": "…", "errors": {"phone": ["…"], …}} -> rejected
 *   401 {"message": "…"}                              -> not logged in
 */
import { initProfileForm } from "./profile-core.js";
import { rules, filters, formatPhone } from "./profile-rules.js";

function initProfile() {
    initProfileForm({
        formId: "account-profile-form",
        transport: "json",
        jsonFields: ["first_name", "last_name", "phone", "address"],
        rows: {
            name: ["first_name", "last_name"],
            phone: ["phone"],
            address: ["address"],
        },
        immediate: ["first_name", "last_name", "phone"],
        validators: {
            first_name: rules.first_name,
            last_name: rules.last_name,
            phone: rules.phoneOptional, // may stay empty; when typed, same rule as the booking Contact Number
            address: rules.address,
        },
        filters: {
            first_name: filters.first_name,
            last_name: filters.last_name,
            phone: filters.phone,
        },
        display: {
            name: (v) => `${v.first_name} ${v.last_name}`.trim(),
            phone: (v) => formatPhone(v.phone),
            address: (v) => v.address,
        },
    });
}

document.addEventListener("DOMContentLoaded", initProfile);
