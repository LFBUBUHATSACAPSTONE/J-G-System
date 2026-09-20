// resources/js/login.js

/**
 * Login submit (AJAX)
 *
 * Intercepts the login form, posts JSON, and on success reloads/redirects
 * into the logged-in state. Failures show inline under the fields.
 *
 * Expected back-end contract for `login`:
 *   200 JSON (optional {"redirect": "/dashboard"}) -> authenticated
 *   422 {"message": "…", "errors": {"identifier": ["…"]}} -> rejected
 * `remember` is sent as a boolean for Auth::attempt($credentials, $remember).
 */

const AUTH_MODAL_ID = "authModal";
const VIEW_SELECTOR = '[data-view="login"]';

function initLogin() {
    const modalEl = document.getElementById(AUTH_MODAL_ID);
    if (!modalEl) return;

    modalEl.addEventListener("submit", (event) => {
        const form = event.target;
        if (!form.closest(VIEW_SELECTOR)) return;
        event.preventDefault();
        submitLogin(form);
    });

    modalEl.addEventListener("input", (event) => {
        const form = event.target.closest("form");
        if (form?.closest(VIEW_SELECTOR)) clearError(form);
    });

    modalEl.addEventListener("hidden.bs.modal", () => {
        const form = modalEl.querySelector(`${VIEW_SELECTOR} form`);
        if (form) {
            form.reset();
            clearError(form);
        }
    });
}

async function submitLogin(form) {
    const identifier = form
        .querySelector('input[name="identifier"]')
        .value.trim();
    const password = form.querySelector('input[name="password"]').value;
    const remember = form.querySelector('input[name="remember"]').checked;
    const submitBtn = form.querySelector('[type="submit"]');

    clearError(form);
    submitBtn.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    form.querySelector('input[name="_token"]')?.value ?? "",
            },
            body: JSON.stringify({ identifier, password, remember }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            showError(
                form,
                Object.values(data.errors ?? {})[0]?.[0] ||
                    data.message ||
                    "Invalid credentials. Please try again.",
            );
            return;
        }

        const data = await response.json().catch(() => ({}));
        window.location.assign(data.redirect || window.location.href);
    } catch {
        showError(form, "Something went wrong. Please try again.");
    } finally {
        submitBtn.disabled = false;
    }
}

function showError(form, message) {
    const errorEl = form.querySelector("[data-login-error]");
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("d-none");
    }
    form.querySelectorAll(
        'input[name="identifier"], input[name="password"]',
    ).forEach((input) => input.classList.add("is-invalid"));
}

function clearError(form) {
    const errorEl = form.querySelector("[data-login-error]");
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("d-none");
    }
    form.querySelectorAll("input").forEach((input) =>
        input.classList.remove("is-invalid"),
    );
}

document.addEventListener("DOMContentLoaded", initLogin);

export { initLogin };
