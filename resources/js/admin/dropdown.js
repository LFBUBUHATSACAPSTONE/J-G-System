// Admin dropdowns: replaces the browser's own <select> menu with a styled listbox.
// The real <select> stays in the DOM (visually hidden), so bookings.js / history.js, forms and
// `change` listeners keep working untouched. Add to resources/js/admin.js, BEFORE the page scripts:
// Picks up: select.admin-filter__select and select.admin-field__control (or any select[data-dropdown]).
// Pattern: WAI-ARIA "select-only combobox" (button + listbox, aria-activedescendant).

// Tailwind class strings (kept whole so Tailwind can find them). The old admin-dropdown* names stay only
// where other Sass still targets them (admin-dropdown, admin-dropdown__trigger: the invalid look in the booking modal).
const TRIGGER_BASE =
    "admin-dropdown__trigger group/trigger flex w-full cursor-pointer items-center justify-between gap-3 text-left text-fluid-sm text-ink disabled:cursor-default";
const TRIGGER_FILTER = `${TRIGGER_BASE} border-0 bg-transparent px-0 py-2 focus-visible:outline-0`;
const TRIGGER_FIELD = `${TRIGGER_BASE} admin-field__control rounded-field border border-field-border bg-surface px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text`;
const VALUE = "min-w-0 flex-1 truncate data-placeholder:text-ink-subtle";
const CHEVRON =
    "ph ph-caret-down flex-none text-fluid-base text-ink transition-transform duration-200 group-aria-expanded/trigger:rotate-180 group-disabled/trigger:hidden motion-reduce:transition-none";
const MENU =
    "fixed z-[1080] m-0 list-none origin-top overflow-y-auto overscroll-contain rounded-panel border border-dropdown-border bg-surface p-2 shadow-[0_12px_32px_-8px_rgb(0_0_0/0.22),0_2px_6px_rgb(0_0_0/0.08)] animate-dropdown-in motion-reduce:animate-none [&[hidden]]:hidden";
const OPTION =
    "group/option flex min-h-10 cursor-pointer select-none items-center justify-between gap-4 rounded-field px-4 py-2 text-fluid-sm leading-[1.3] text-ink not-first:mt-1 data-active:not-aria-selected:bg-panel aria-selected:bg-brand-soft aria-selected:font-semibold aria-selected:text-brand-text";
const CHECK =
    "ph ph-check flex-none text-fluid-base opacity-0 group-aria-selected/option:opacity-100";

const SELECTOR =
    "select.admin-filter__select, select.admin-field__control, select[data-dropdown]";
const nativeValue = Object.getOwnPropertyDescriptor(
    HTMLSelectElement.prototype,
    "value",
);
let uid = 0;
let openInstance = null;

function enhance(select) {
    if (select.dataset.dropdownReady) return;
    select.dataset.dropdownReady = "1";

    const id = `admin-dd-${++uid}`;
    const nativeId = select.id;
    const isField = select.classList.contains("admin-field__control");

    // ---- Build markup ---------------------------------------------------------
    const root = document.createElement("div");
    root.className = "admin-dropdown relative min-w-0";

    const trigger = document.createElement("button");
    trigger.type = "button";
    trigger.className = isField ? TRIGGER_FIELD : TRIGGER_FILTER;
    trigger.setAttribute("role", "combobox");
    trigger.setAttribute("aria-haspopup", "listbox");
    trigger.setAttribute("aria-expanded", "false");
    trigger.setAttribute("aria-controls", `${id}-list`);
    if (nativeId) trigger.id = nativeId; // so <label for="..."> now targets the visible control
    if (select.getAttribute("aria-label"))
        trigger.setAttribute("aria-label", select.getAttribute("aria-label"));

    const valueEl = document.createElement("span");
    valueEl.className = VALUE;
    const chevron = document.createElement("i");
    chevron.className = CHEVRON;
    chevron.setAttribute("aria-hidden", "true");
    trigger.append(valueEl, chevron);

    const list = document.createElement("ul");
    list.className = MENU;
    list.id = `${id}-list`;
    list.setAttribute("role", "listbox");
    list.tabIndex = -1;
    list.hidden = true;

    const items = [...select.options].map((option, i) => {
        const li = document.createElement("li");
        li.className = OPTION;
        li.id = `${id}-opt-${i}`;
        li.setAttribute("role", "option");
        li.dataset.value = option.value;
        li.innerHTML = `<span></span><i class="${CHECK}" aria-hidden="true"></i>`;
        li.firstElementChild.textContent = option.textContent.trim();
        list.appendChild(li);
        return li;
    });

    select.before(root);
    root.append(select, trigger, list);
    select.classList.add("sr-only", "pointer-events-none");
    select.tabIndex = -1;
    select.setAttribute("aria-hidden", "true");
    select.removeAttribute("id"); // id moved to the trigger

    const anchor = () => select.closest(".admin-filter") || trigger;
    let active = -1;
    let typed = "";
    let typedTimer;

    // ---- State sync -----------------------------------------------------------
    function sync() {
        const index = Math.max(select.selectedIndex, 0);
        valueEl.textContent = items[index]?.firstElementChild.textContent ?? "";
        valueEl.toggleAttribute(
            "data-placeholder",
            select.value === "" && index === 0,
        );
        items.forEach((li, i) =>
            li.setAttribute("aria-selected", String(i === index)),
        );
        trigger.disabled = select.disabled;
        if (select.disabled) close();
    }

    function setActive(i) {
        active = Math.min(Math.max(i, 0), items.length - 1);
        items.forEach((li, n) =>
            li.toggleAttribute("data-active", n === active),
        );
        trigger.setAttribute("aria-activedescendant", items[active].id);
        items[active].scrollIntoView({ block: "nearest" });
    }

    function choose(i) {
        const changed = select.selectedIndex !== i;
        select.selectedIndex = i;
        sync();
        close(true);
        if (changed)
            select.dispatchEvent(new Event("change", { bubbles: true }));
    }

    // ---- Position (fixed, so modals and scroll containers never clip it) -----
    function place() {
        const rect = anchor().getBoundingClientRect();
        const gap = 8;
        const below = window.innerHeight - rect.bottom - gap;
        const above = rect.top - gap;
        const height = Math.min(list.scrollHeight, 18 * 16);
        const flip = below < Math.min(height, 200) && above > below;

        list.style.left = `${rect.left}px`;
        list.style.minWidth = `${rect.width}px`;
        list.style.maxHeight = `${Math.max(120, Math.min(18 * 16, (flip ? above : below) - 4))}px`;
        list.style.top = flip ? "auto" : `${rect.bottom + gap}px`;
        list.style.bottom = flip
            ? `${window.innerHeight - rect.top + gap}px`
            : "auto";
        list.classList.toggle("origin-bottom", flip);
        list.classList.toggle("origin-top", !flip);
    }

    function open() {
        if (select.disabled || !list.hidden) return;
        openInstance?.close();
        openInstance = { close };
        list.hidden = false;
        anchor().setAttribute("data-open", "");
        trigger.setAttribute("aria-expanded", "true");
        place();
        setActive(Math.max(select.selectedIndex, 0));
        window.addEventListener("resize", place);
        window.addEventListener("scroll", place, true);
    }

    function close(refocus = false) {
        if (list.hidden) return;
        list.hidden = true;
        anchor().removeAttribute("data-open");
        trigger.setAttribute("aria-expanded", "false");
        trigger.removeAttribute("aria-activedescendant");
        window.removeEventListener("resize", place);
        window.removeEventListener("scroll", place, true);
        if (openInstance?.close === close) openInstance = null;
        if (refocus) trigger.focus();
    }

    // Events
    trigger.addEventListener("click", () => (list.hidden ? open() : close()));

    list.addEventListener("mousedown", (e) => e.preventDefault()); // keep focus on the trigger
    list.addEventListener("click", (e) => {
        const li = e.target.closest('[role="option"]');
        if (li) choose(items.indexOf(li));
    });
    list.addEventListener("mousemove", (e) => {
        const li = e.target.closest('[role="option"]');
        if (li && items.indexOf(li) !== active) setActive(items.indexOf(li));
    });

    trigger.addEventListener("keydown", (e) => {
        const isOpen = !list.hidden;
        switch (e.key) {
            case "ArrowDown":
            case "ArrowUp":
                e.preventDefault();
                if (!isOpen) open();
                else setActive(active + (e.key === "ArrowDown" ? 1 : -1));
                break;
            case "Home":
            case "End":
                if (isOpen) {
                    e.preventDefault();
                    setActive(e.key === "Home" ? 0 : items.length - 1);
                }
                break;
            case "Enter":
            case " ":
                e.preventDefault();
                if (isOpen) choose(active);
                else open();
                break;
            case "Escape":
                if (isOpen) {
                    e.preventDefault();
                    e.stopPropagation();
                    close(true);
                } // don't also close a modal
                break;
            case "Tab":
                close();
                break;
            default:
                if (
                    e.key.length === 1 &&
                    !e.ctrlKey &&
                    !e.metaKey &&
                    !e.altKey
                ) {
                    typed += e.key.toLowerCase();
                    clearTimeout(typedTimer);
                    typedTimer = setTimeout(() => (typed = ""), 600);
                    const hit = items.findIndex((li) =>
                        li.textContent.trim().toLowerCase().startsWith(typed),
                    );
                    if (hit > -1) {
                        if (list.hidden) open();
                        setActive(hit);
                    }
                }
        }
    });

    document.addEventListener("mousedown", (e) => {
        if (!list.hidden && !root.contains(e.target)) close();
    });

    // Code elsewhere sets `select.value = ...`, `select.disabled = ...` and calls `select.focus()`.
    Object.defineProperty(select, "value", {
        configurable: true,
        get() {
            return nativeValue.get.call(this);
        },
        set(v) {
            nativeValue.set.call(this, v);
            sync();
        },
    });
    select.focus = (opts) => trigger.focus(opts);
    new MutationObserver(sync).observe(select, {
        attributes: true,
        attributeFilter: ["disabled"],
    });
    select.form?.addEventListener("reset", () => setTimeout(sync));

    sync();
}

function init() {
    document.querySelectorAll(SELECTOR).forEach(enhance);
}

if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", init);
else init();

export { enhance };
