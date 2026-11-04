// Admin dropdowns: replaces the browser's own <select> menu with a styled listbox.
// The real <select> stays in the DOM (visually hidden), so bookings.js / history.js, forms and
// `change` listeners keep working untouched. Add to resources/js/admin.js, BEFORE the page scripts:
// Picks up: select.admin-filter__select and select.admin-field__control (or any select[data-dropdown]).
// Pattern: WAI-ARIA "select-only combobox" (button + listbox, aria-activedescendant).

const SELECTOR = 'select.admin-filter__select, select.admin-field__control, select[data-dropdown]';
const nativeValue = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
let uid = 0;
let openInstance = null;

function enhance(select) {
    if (select.dataset.dropdownReady) return;
    select.dataset.dropdownReady = '1';

    const id = `admin-dd-${++uid}`;
    const nativeId = select.id;
    const isField = select.classList.contains('admin-field__control');

    // ---- Build markup ---------------------------------------------------------
    const root = document.createElement('div');
    root.className = `admin-dropdown${isField ? ' admin-dropdown--field' : ''}`;

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = `admin-dropdown__trigger${isField ? ' admin-field__control' : ''}`;
    trigger.setAttribute('role', 'combobox');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', `${id}-list`);
    if (nativeId) trigger.id = nativeId; // so <label for="..."> now targets the visible control
    if (select.getAttribute('aria-label')) trigger.setAttribute('aria-label', select.getAttribute('aria-label'));

    const valueEl = document.createElement('span');
    valueEl.className = 'admin-dropdown__value';
    const chevron = document.createElement('i');
    chevron.className = 'ph ph-caret-down admin-dropdown__chevron';
    chevron.setAttribute('aria-hidden', 'true');
    trigger.append(valueEl, chevron);

    const list = document.createElement('ul');
    list.className = 'admin-dropdown__menu';
    list.id = `${id}-list`;
    list.setAttribute('role', 'listbox');
    list.tabIndex = -1;
    list.hidden = true;

    const items = [...select.options].map((option, i) => {
        const li = document.createElement('li');
        li.className = 'admin-dropdown__option';
        li.id = `${id}-opt-${i}`;
        li.setAttribute('role', 'option');
        li.dataset.value = option.value;
        li.innerHTML = '<span class="admin-dropdown__text"></span><i class="ph ph-check admin-dropdown__check" aria-hidden="true"></i>';
        li.firstElementChild.textContent = option.textContent.trim();
        list.appendChild(li);
        return li;
    });

    select.before(root);
    root.append(select, trigger, list);
    select.classList.add('admin-dropdown__native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    select.removeAttribute('id'); // id moved to the trigger

    const anchor = () => select.closest('.admin-filter') || trigger;
    let active = -1;
    let typed = '';
    let typedTimer;

    // ---- State sync -----------------------------------------------------------
    function sync() {
        const index = Math.max(select.selectedIndex, 0);
        valueEl.textContent = items[index]?.firstElementChild.textContent ?? '';
        valueEl.classList.toggle('is-placeholder', select.value === '' && index === 0);
        items.forEach((li, i) => li.setAttribute('aria-selected', String(i === index)));
        trigger.disabled = select.disabled;
        root.classList.toggle('is-disabled', select.disabled);
        if (select.disabled) close();
    }

    function setActive(i) {
        active = Math.min(Math.max(i, 0), items.length - 1);
        items.forEach((li, n) => li.classList.toggle('is-active', n === active));
        trigger.setAttribute('aria-activedescendant', items[active].id);
        items[active].scrollIntoView({ block: 'nearest' });
    }

    function choose(i) {
        const changed = select.selectedIndex !== i;
        select.selectedIndex = i;
        sync();
        close(true);
        if (changed) select.dispatchEvent(new Event('change', { bubbles: true }));
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
        list.style.top = flip ? 'auto' : `${rect.bottom + gap}px`;
        list.style.bottom = flip ? `${window.innerHeight - rect.top + gap}px` : 'auto';
        root.classList.toggle('is-flipped', flip);
    }

    function open() {
        if (select.disabled || !list.hidden) return;
        openInstance?.close();
        openInstance = { close };
        list.hidden = false;
        root.classList.add('is-open');
        anchor().classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        place();
        setActive(Math.max(select.selectedIndex, 0));
        window.addEventListener('resize', place);
        window.addEventListener('scroll', place, true);
    }

    function close(refocus = false) {
        if (list.hidden) return;
        list.hidden = true;
        root.classList.remove('is-open');
        anchor().classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.removeAttribute('aria-activedescendant');
        window.removeEventListener('resize', place);
        window.removeEventListener('scroll', place, true);
        if (openInstance?.close === close) openInstance = null;
        if (refocus) trigger.focus();
    }

    // Events 
    trigger.addEventListener('click', () => (list.hidden ? open() : close()));

    list.addEventListener('mousedown', (e) => e.preventDefault()); // keep focus on the trigger
    list.addEventListener('click', (e) => {
        const li = e.target.closest('[role="option"]');
        if (li) choose(items.indexOf(li));
    });
    list.addEventListener('mousemove', (e) => {
        const li = e.target.closest('[role="option"]');
        if (li && items.indexOf(li) !== active) setActive(items.indexOf(li));
    });

    trigger.addEventListener('keydown', (e) => {
        const isOpen = !list.hidden;
        switch (e.key) {
            case 'ArrowDown':
            case 'ArrowUp':
                e.preventDefault();
                if (!isOpen) open();
                else setActive(active + (e.key === 'ArrowDown' ? 1 : -1));
                break;
            case 'Home':
            case 'End':
                if (isOpen) { e.preventDefault(); setActive(e.key === 'Home' ? 0 : items.length - 1); }
                break;
            case 'Enter':
            case ' ':
                e.preventDefault();
                if (isOpen) choose(active); else open();
                break;
            case 'Escape':
                if (isOpen) { e.preventDefault(); e.stopPropagation(); close(true); } // don't also close a modal
                break;
            case 'Tab':
                close();
                break;
            default:
                if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    typed += e.key.toLowerCase();
                    clearTimeout(typedTimer);
                    typedTimer = setTimeout(() => (typed = ''), 600);
                    const hit = items.findIndex((li) => li.textContent.trim().toLowerCase().startsWith(typed));
                    if (hit > -1) { if (list.hidden) open(); setActive(hit); }
                }
        }
    });

    document.addEventListener('mousedown', (e) => {
        if (!list.hidden && !root.contains(e.target)) close();
    });

    // Code elsewhere sets `select.value = ...`, `select.disabled = ...` and calls `select.focus()`.
    Object.defineProperty(select, 'value', {
        configurable: true,
        get() { return nativeValue.get.call(this); },
        set(v) { nativeValue.set.call(this, v); sync(); },
    });
    select.focus = (opts) => trigger.focus(opts);
    new MutationObserver(sync).observe(select, { attributes: true, attributeFilter: ['disabled'] });
    select.form?.addEventListener('reset', () => setTimeout(sync));

    sync();
}

function init() {
    document.querySelectorAll(SELECTOR).forEach(enhance);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();

export { enhance };