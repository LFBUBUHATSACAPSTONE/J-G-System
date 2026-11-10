// Admin entry.
import "bootstrap";
import "@phosphor-icons/web/regular";

import "./shared/toast.js"; // toasts: showToast() and the server flash (see docs/toast-notifications.md)
import "./shared/toast-feed.js"; // messages between the two parties (Phase 4)
import "./admin/dropdown.js"; // enhances <select> to a styled listbox (see _dropdown.scss)
import "./admin/dashboard.js";
import "./admin/bookings.js";
import "./admin/packages.js";
import "./admin/calendar.js";
import "./admin/history.js";
import "./admin/account.js";
import "./admin/messages.js";
import "./auth/logout.js"; // "Log out?" modal, then the JSON logout
