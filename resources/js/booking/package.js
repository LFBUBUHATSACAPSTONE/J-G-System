
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

            event.preventDefault();

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