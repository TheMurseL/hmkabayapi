'use strict';

const sidebar = document.getElementById('admin-sidebar');
const menuToggle = document.querySelector('[data-menu-toggle]');
const mobileViewport = window.matchMedia('(max-width: 767px)');

const setMenuOpen = (open) => {
    document.body.classList.toggle('menu-open', open);
    menuToggle?.setAttribute('aria-expanded', String(open));
    if (sidebar && mobileViewport.matches) {
        sidebar.inert = !open;
    }
    if (open) {
        sidebar?.querySelector('a')?.focus();
    }
};

menuToggle?.addEventListener('click', () => {
    setMenuOpen(!document.body.classList.contains('menu-open'));
});

document.querySelectorAll('[data-menu-close]').forEach((button) => {
    button.addEventListener('click', () => {
        setMenuOpen(false);
        menuToggle?.focus();
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.classList.contains('menu-open')) {
        setMenuOpen(false);
        menuToggle?.focus();
    }
    if (event.key === 'Tab' && document.body.classList.contains('menu-open') && sidebar) {
        const items = Array.from(sidebar.querySelectorAll('a, button')).filter((item) => item.getClientRects().length);
        const first = items[0];
        const last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    }
});

const syncViewport = () => {
    setMenuOpen(false);
    if (sidebar) {
        sidebar.inert = mobileViewport.matches;
    }
};
mobileViewport.addEventListener('change', syncViewport);
syncViewport();

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.textContent = visible ? 'Gizle' : 'Göster';
        button.setAttribute('aria-label', visible ? 'Şifreyi gizle' : 'Şifreyi göster');
        button.setAttribute('aria-pressed', String(visible));
    });
});

document.querySelectorAll('[data-table-search]').forEach((input) => {
    const section = input.closest('.admin-section');
    const rows = Array.from(section.querySelectorAll('.admin-table tr')).filter((row) => row.querySelector('td'));
    const count = section.querySelector('[data-table-count]');
    const filterRows = () => {
        const query = input.value.trim().toLocaleLowerCase('tr');
        let visible = 0;
        rows.forEach((row) => {
            row.hidden = !row.textContent.toLocaleLowerCase('tr').includes(query);
            if (!row.hidden) {
                visible++;
            }
        });
        count.textContent = `${visible} / ${rows.length} kayıt`;
    };
    input.addEventListener('input', filterRows);
    filterRows();
});

document.querySelectorAll('[data-repeater]').forEach((group) => {
    const rows = group.querySelector('[data-repeater-rows]');
    const addButton = group.querySelector('[data-add-row]');
    const template = group.querySelector('template');

    const updateRows = () => {
        const items = rows.querySelectorAll('[data-repeater-row]');
        items.forEach((row, index) => {
            row.querySelectorAll('[data-pair]').forEach((input) => {
                input.name = `${group.dataset.repeater}[${index}][${input.dataset.pair}]`;
            });
        });
        addButton.disabled = items.length >= 30;
    };

    addButton.addEventListener('click', () => {
        if (rows.children.length >= 30) {
            return;
        }
        const newRow = template.content.cloneNode(true);
        rows.appendChild(newRow);
        updateRows();
        rows.lastElementChild.querySelector('input').focus();
    });

    rows.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-row]');
        if (button) {
            button.closest('[data-repeater-row]').remove();
            updateRows();
        }
    });

    updateRows();
});
