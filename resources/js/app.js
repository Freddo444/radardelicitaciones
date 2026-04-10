import './bootstrap';
import Alpine from 'alpinejs';

window.filterDropdownPick = function (formId, inputId, value, submitOnChange, labelForButton) {
    const form = document.getElementById(formId);
    const input = document.getElementById(inputId);
    if (!form || !input) {
        return;
    }
    input.value = value === undefined || value === null ? '' : String(value);
    if (labelForButton != null && labelForButton !== '') {
        const root = input.closest('[data-filter-dropdown]');
        const labelEl = root?.querySelector('[data-filter-dropdown-label]');
        if (labelEl) {
            labelEl.textContent = labelForButton;
        }
    }
    if (submitOnChange) {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }
};

document.addEventListener('click', (e) => {
    const t = e.target.closest('[data-filter-pick]');
    if (!t) {
        return;
    }
    filterDropdownPick(
        t.dataset.filterForm,
        t.dataset.filterInput,
        t.dataset.filterValue ?? '',
        t.dataset.filterSubmit === '1',
        t.dataset.filterLabel || undefined,
    );
});

document.addEventListener('alpine:init', () => {
    Alpine.store('layout', {
        sidebarCollapsed: false,
        navSections: {
            monitor: true,
            inteligencia: false,
            empresa: true,
            ofertas: true,
            empresaActiva: true,
            sistema: true,
        },
        _hydrated: false,

        initFromPage(routeActive) {
            if (this._hydrated) {
                return;
            }
            this._hydrated = true;
            try {
                this.sidebarCollapsed = JSON.parse(localStorage.getItem('sidebarCollapsed') ?? 'false');
            } catch {
                this.sidebarCollapsed = false;
            }
            const defaults = {
                monitor: true,
                inteligencia: false,
                empresa: true,
                ofertas: true,
                empresaActiva: true,
                sistema: true,
            };
            let saved = {};
            try {
                saved = JSON.parse(localStorage.getItem('sidebarNavSections') || '{}');
            } catch {
                saved = {};
            }
            for (const key of Object.keys(defaults)) {
                const inRoute = routeActive[key] === true;
                const savedVal = Object.prototype.hasOwnProperty.call(saved, key) ? saved[key] : undefined;
                this.navSections[key] = inRoute
                    ? true
                    : (typeof savedVal === 'boolean' ? savedVal : defaults[key]);
            }
        },

        persistSidebar() {
            localStorage.setItem('sidebarCollapsed', JSON.stringify(this.sidebarCollapsed));
        },

        persistNavSections() {
            localStorage.setItem('sidebarNavSections', JSON.stringify(this.navSections));
        },

        toggleSidebarCollapsed() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            this.persistSidebar();
        },

        toggleNavSection(key, isMobileDrawer) {
            if (this.sidebarCollapsed && !isMobileDrawer) {
                return;
            }
            this.navSections[key] = !this.navSections[key];
            this.persistNavSections();
        },

        sectionOpen(key, isMobileDrawer) {
            return (this.sidebarCollapsed && !isMobileDrawer) || this.navSections[key];
        },
    });
});

window.Alpine = Alpine;
Alpine.start();
