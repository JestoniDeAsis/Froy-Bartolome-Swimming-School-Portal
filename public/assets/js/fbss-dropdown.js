/* =============================================================
   FBSS Portal — shared dropdown behaviour
   Place at: public/assets/js/fbss-dropdown.js

   Pairs with the .registerDropdown component in our_theme.css.
   One set of document-level listeners drives EVERY dropdown that has
   the `data-fbss-dropdown` attribute, so options added later (from the
   database) work without re-initialising anything.

   Markup:
     <div class="registerDropdown" data-fbss-dropdown id="myFilter">
       <button class="registerDropdownToggle" type="button"
               aria-expanded="false" aria-controls="myFilterOptions">
         <span>All</span>
         <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
       </button>
       <div class="registerDropdownOptions" id="myFilterOptions" hidden>
         <button class="registerDropdownOption active" type="button" data-value="">All</button>
         <button class="registerDropdownOption" type="button" data-value="a">Option A</button>
       </div>
       <input type="hidden" name="myField" value="">
     </div>

   Listen for changes (bubbles from the .registerDropdown element):
     document.getElementById('myFilter')
       .addEventListener('dropdown:change', e => e.detail.value / e.detail.label);

   Helpers:
     FBSSDropdown.get(root)            -> current value
     FBSSDropdown.set(root, value)     -> select by value (silent unless 3rd arg true)
     FBSSDropdown.setOptions(root, [{value,label}], selectedValue)
                                       -> rebuild the option list
   Size: set CSS variables on the root (--dd-min-width, --dd-height ...).
   ============================================================= */
(function () {
    'use strict';

    if (window.FBSSDropdown) return;   // safe if the file is included twice

    const ROOT = '[data-fbss-dropdown]';
    const GAP = 8;

    const parts = root => ({
        toggle: root.querySelector('.registerDropdownToggle'),
        panel: root.querySelector('.registerDropdownOptions'),
        label: root.querySelector('.registerDropdownToggle span'),
        input: root.querySelector('input[type="hidden"]')
    });

    function cssNumber(root, name, fallback) {
        const raw = parseFloat(getComputedStyle(root).getPropertyValue(name));
        return Number.isFinite(raw) ? raw : fallback;
    }

    // The panel is position: fixed (never clipped by overflow parents), so it is
    // placed from the toggle's viewport rect. Flips above the toggle when there
    // isn't room below.
    function position(root) {
        const { toggle, panel } = parts(root);
        if (!toggle || !panel || panel.hidden) return;

        const rect = toggle.getBoundingClientRect();
        const scale = cssNumber(root, '--dd-panel-scale', 1);
        const minWidth = cssNumber(root, '--dd-panel-min-width', 0);
        const width = Math.min(Math.max(rect.width * scale, minWidth), window.innerWidth - 16);

        panel.style.width = `${width}px`;
        panel.style.left = `${Math.max(8, Math.min(rect.left, window.innerWidth - width - 8))}px`;

        const height = panel.offsetHeight;
        const below = window.innerHeight - rect.bottom - GAP;
        const above = rect.top - GAP;

        if (height > below && above > below) {
            panel.style.top = `${Math.max(8, rect.top - GAP - height)}px`;
        } else {
            panel.style.top = `${rect.bottom + GAP}px`;
        }
    }

    function close(root) {
        const { toggle, panel } = parts(root);
        root.classList.remove('isOpen');
        if (panel) panel.hidden = true;
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }

    function closeAll(except) {
        document.querySelectorAll(ROOT).forEach(root => { if (root !== except) close(root); });
    }

    function open(root) {
        const { toggle, panel } = parts(root);
        if (!toggle || !panel) return;
        closeAll(root);
        root.classList.add('isOpen');
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        position(root);
    }

    function select(root, option, announce) {
        const { panel, label, input } = parts(root);
        const value = option.dataset.value ?? '';
        const label_ = option.textContent.trim();
        const changed = !input || input.value !== value;

        if (label) label.textContent = label_;
        if (input) input.value = value;
        panel.querySelectorAll('.registerDropdownOption').forEach(o => o.classList.toggle('active', o === option));

        if (announce && changed) {
            root.dispatchEvent(new CustomEvent('dropdown:change', { bubbles: true, detail: { value, label: label_ } }));
        }
    }

    function findOption(root, value) {
        return Array.from(root.querySelectorAll('.registerDropdownOption'))
            .find(o => (o.dataset.value ?? '') === String(value ?? ''));
    }

    document.addEventListener('click', event => {
        const root = event.target.closest(ROOT);

        if (!root) { closeAll(); return; }

        const option = event.target.closest('.registerDropdownOption');
        if (option) {
            select(root, option, true);
            close(root);
            return;
        }

        if (event.target.closest('.registerDropdownToggle')) {
            const { panel } = parts(root);
            panel && panel.hidden ? open(root) : close(root);
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeAll();
    });

    const reposition = () => document.querySelectorAll(`${ROOT}.isOpen`).forEach(position);
    window.addEventListener('resize', reposition);
    document.addEventListener('scroll', reposition, true);

    window.FBSSDropdown = {
        get(root) {
            const { input } = parts(root);
            return input ? input.value : '';
        },
        set(root, value, announce = false) {
            const option = findOption(root, value);
            if (option) select(root, option, announce);
        },
        setOptions(root, items, selectedValue = '') {
            const { panel } = parts(root);
            panel.replaceChildren();
            items.forEach(item => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'registerDropdownOption';
                button.dataset.value = String(item.value);
                button.textContent = item.label;
                panel.append(button);
            });
            const option = findOption(root, selectedValue) || panel.querySelector('.registerDropdownOption');
            if (option) select(root, option, false);
        },
        close: closeAll
    };
})();