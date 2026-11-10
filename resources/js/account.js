// Account entry: its own Vite entry so none of this ships to the public pages.
import "bootstrap";
import "@phosphor-icons/web/regular";

import "./shared/toast.js"; // toasts: showToast() / queueToast() (docs/toast-notifications.md)
import "./shared/toast-feed.js"; // messages between the two parties (Phase 4)
import "./account/profile.js";
import "./account/bookings.js";
import "./account/messages.js";
import "./account/security.js";
import "./account/delete.js";
import "./auth/logout.js";
