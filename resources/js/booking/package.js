const SELECTOR = "[data-package-selection]";

function initPackageSelection() {
    const sections = document.querySelectorAll(SELECTOR);
    sections.forEach((section) => {
        section.addEventListener("click", (event) => {
            const trigger = event.target.closest("[data-package-select]");
            if (!trigger || !section.contains(trigger)) return;

            // Links (href-based cards) navigate normally; only
            // buttons need the event dispatched here.
            if (trigger.tagName === "A") return;

            const card = trigger.closest("[data-package-card]");
            if (!card) return;

            // A trigger that opens the auth modal (data-bs-toggle) must
            // keep its default behaviour; plain buttons don't need it.
            if (!trigger.hasAttribute("data-bs-toggle")) {
                event.preventDefault();
            }

            trigger.dispatchEvent(
                new CustomEvent("booking:package-saved", {
                    bubbles: true,
                    detail: {
                        name: card.dataset.packageName,
                        cost: Number(card.dataset.packageCost),
                    },
                }),
            );
        });
    });
}

document.addEventListener("DOMContentLoaded", initPackageSelection);

export { initPackageSelection };
