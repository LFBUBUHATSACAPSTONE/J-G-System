/**
 * Shared CSRF token lookup for every fetch() submit in the auth modal and
 * booking flow.
 *
 * Read at call time (not import time) so a rotated token is never stale.
 * Prefers the layout's <meta name="csrf-token">; falls back to the form's
 * own @csrf hidden input for pages that don't include the meta tag.
 * Send the result as the `X-CSRF-TOKEN` header only.
 */
export function getCsrfToken(form) {
    return (
        document.head.querySelector('meta[name="csrf-token"]')?.content ||
        form?.querySelector('input[name="_token"]')?.value ||
        ""
    );
}
