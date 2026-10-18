(() => {
    const button = document.querySelector('[data-menu]');
    const nav = document.querySelector('[data-nav]');
    const dropdowns = [...document.querySelectorAll('[data-nav-dropdown]')];
    const desktopHover = window.matchMedia('(min-width: 1201px) and (hover: hover) and (pointer: fine)');
    const closeDropdowns = (except = null) => {
        dropdowns.forEach((dropdown) => { if (dropdown !== except) dropdown.open = false; });
    };
    const closeMenu = () => {
        nav?.classList.remove('is-open');
        button?.setAttribute('aria-expanded', 'false');
        button?.setAttribute('aria-label', 'Menüyü aç');
        closeDropdowns();
    };
    button?.addEventListener('click', () => {
        const open = nav?.classList.toggle('is-open');
        button.setAttribute('aria-expanded', String(Boolean(open)));
        button.setAttribute('aria-label', open ? 'Menüyü kapat' : 'Menüyü aç');
        if (!open) closeDropdowns();
    });
    dropdowns.forEach((dropdown) => {
        dropdown.addEventListener('toggle', () => { if (dropdown.open) closeDropdowns(dropdown); });
        dropdown.addEventListener('pointerenter', (e) => {
            if (!desktopHover.matches || e.pointerType !== 'mouse') return;
            closeDropdowns(dropdown);
            dropdown.open = true;
        });
        dropdown.addEventListener('pointerleave', (e) => {
            if (desktopHover.matches && e.pointerType === 'mouse' && !dropdown.querySelector(':focus-visible')) {
                dropdown.open = false;
            }
        });
        dropdown.querySelector('summary').addEventListener('click', (e) => {
            if (desktopHover.matches && e.pointerType === 'mouse') {
                e.preventDefault();
                closeDropdowns(dropdown);
                dropdown.open = true;
            }
        });
    });
    document.addEventListener('focusin', (e) => closeDropdowns(e.target.closest('[data-nav-dropdown]')));
    document.addEventListener('click', (e) => {
        if (!nav?.contains(e.target) && !button?.contains(e.target)) closeMenu();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        const openDropdown = dropdowns.find((dropdown) => dropdown.open);
        if (openDropdown) {
            closeDropdowns();
            openDropdown.querySelector('summary').focus();
            e.preventDefault();
        } else if (nav?.classList.contains('is-open')) {
            closeMenu();
            button?.focus();
            e.preventDefault();
        }
    });
    window.matchMedia('(max-width: 1200px)').addEventListener('change', closeMenu);
    if (window.Fancybox) Fancybox.bind('[data-fancybox="project-gallery"]', { Thumbs: { autoStart: true }, Toolbar: { display: ['close'] } });
    const event = (name, parameters = {}) => {
        // Never include names, phone numbers, emails, free text or URL query strings.
        document.dispatchEvent(new CustomEvent('site:measurement', { detail: { name, parameters } }));
    };
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link) return;
        const type = link.href.startsWith('tel:') ? 'phone' : link.href.startsWith('https://wa.me/') ? 'whatsapp' : null;
        if (type) event('contact_click', { contact_type: type });
    });
    if (document.querySelector('[data-lead-success]')) event('generate_lead', { lead_type: 'quote_form' });
    const calculator = document.querySelector('[data-calculator]');
    const money = (value) => new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 2 }).format(value) + ' ₺';
    const decimal = (value) => new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 4 }).format(value);
    const number = (selector) => {
        const field = calculator.querySelector(selector);
        return field && field.value !== '' && field.checkValidity() ? Number(field.value) : NaN;
    };
    // Avoid an extra brick when floating point arithmetic yields e.g. 84.00000000000001.
    const wholeBricks = (value) => {
        const nearest = Math.round(value);
        return Math.abs(value - nearest) <= Number.EPSILON * Math.max(1, value) * 8
            ? nearest : Math.ceil(value);
    };
    const calculateBrick = () => {
        const set = (selector, text) => { calculator.querySelector(selector).textContent = text; };
        const error = calculator.querySelector('[data-brick-error]');
        error.hidden = true;
        calculator.querySelectorAll('.calc-breakdown dd').forEach((item) => { item.textContent = '—'; });
        const fail = (message) => {
            error.textContent = message;
            error.hidden = false;
            set('[data-calc-total]', '—');
            set('[data-calc-detail]', 'Ölçüleri kontrol ederek yeniden hesaplayın.');
            return { valid: false, area: NaN };
        };
        const directArea = calculator.querySelector('[data-brick-mode]').value === 'area';
        let gross, openings = 0;
        if (directArea) {
            gross = number('[data-brick-area]');
            if (!Number.isFinite(gross) || gross <= 0) return fail('Geçerli bir net duvar alanı girin.');
        } else {
            const width = number('[data-brick-width]'), height = number('[data-brick-height]');
            if (!(width > 0) || !(height > 0)) return fail('Duvar eni ve yüksekliğini sıfırdan büyük girin.');
            gross = width * height;
            if (calculator.querySelector('[data-brick-has-openings]').checked) {
                const rows = calculator.querySelectorAll('.calc-opening');
                for (const [index, row] of [...rows].entries()) {
                    const read = (selector) => {
                        const field = row.querySelector(selector);
                        return field.value !== '' && field.checkValidity() ? Number(field.value) : NaN;
                    };
                    const width = read('[data-opening-width]'), height = read('[data-opening-height]');
                    const count = read('[data-opening-count]');
                    if (!(width > 0) || !(height > 0) || !Number.isInteger(count) || count < 1) {
                        return fail(`${index + 1}. boşluğun en, yükseklik ve adet bilgilerini kontrol edin.`);
                    }
                    openings += width * height * count;
                }
            }
        }
        const tolerance = Number.EPSILON * Math.max(1, gross, openings) * 8;
        if (openings > gross + tolerance) return fail('Kapı ve pencere boşluklarının toplamı duvar alanından büyük olamaz.');
        const difference = gross - openings;
        const area = Math.abs(difference) <= tolerance ? 0 : difference;
        if (area > 1000000) return fail('Net duvar alanı en fazla 1.000.000 m² olabilir.');
        const consumption = number('[data-brick-size]'), waste = number('[data-brick-waste]');
        if (!(consumption > 0) || !Number.isFinite(waste)) return fail('Geçerli tüketim ve fire payı girin. Fire %0 ile %50 arasında olmalıdır.');
        const rawQuantity = area * consumption;
        const baseQuantity = wholeBricks(rawQuantity);
        const quantity = wholeBricks(rawQuantity * (1 + waste / 100));
        set('[data-calc-total]', `${decimal(quantity)} adet`);
        set('[data-calc-detail]', `${decimal(area)} m² × ${decimal(consumption)} adet/m² · %${decimal(waste)} fire dahil`);
        set('[data-brick-gross]', directArea ? 'Net alan girildi' : `${decimal(gross)} m²`);
        set('[data-brick-opening-area]', directArea ? 'Net alandan düşülmüş' : `${decimal(openings)} m²`);
        set('[data-brick-net]', `${decimal(area)} m²`);
        set('[data-brick-base]', `${decimal(baseQuantity)} adet`);
        set('[data-brick-extra]', `${decimal(quantity - baseQuantity)} adet`);
        const price = number('[data-brick-price]'), labor = number('[data-brick-labor]');
        const materialCost = quantity * price, laborCost = area * labor;
        if (Number.isFinite(price)) set('[data-brick-material-cost]', money(materialCost));
        if (Number.isFinite(labor)) set('[data-brick-labor-cost]', money(laborCost));
        if (Number.isFinite(price) && Number.isFinite(labor)) {
            set('[data-brick-cost]', money(materialCost + laborCost));
        } else {
            set('[data-brick-cost]', 'İki birim fiyatı da girin');
        }
        for (const selector of ['[data-brick-price]', '[data-brick-labor]']) {
            const field = calculator.querySelector(selector);
            if (field.value !== '' && !field.checkValidity()) {
                error.textContent = 'Adet hesabı hazır. Maliyet için birim fiyatları 0 ile 1.000.000 ₺ arasında girin.';
                error.hidden = false;
            }
        }
        return { valid: true, area };
    };
    const calculate = (track = false) => {
        const brick = calculator.dataset.calculator === 'brick';
        let area, valid;
        if (brick) {
            ({ area, valid } = calculateBrick());
        } else {
            area = number('[data-cost-area]');
            const total = area * number('[data-cost-level]');
            valid = Number.isFinite(total) && total > 0;
            calculator.querySelector('[data-calc-total]').textContent = valid ? money(total) : '—';
            calculator.querySelector('[data-calc-detail]').textContent = valid
                ? area.toLocaleString('tr-TR') + ' m² × girilen birim fiyat'
                : 'Geçerli alan ve birim fiyat bilgilerini girin.';
        }
        const quote = new URL(calculator.querySelector('[data-quote-link]').href);
        if (Number.isFinite(area) && area > 0) quote.searchParams.set('area', String(Number(area.toPrecision(12)))); else quote.searchParams.delete('area');
        calculator.querySelector('[data-quote-link]').href = quote.href;
        if (track && valid) event('calculator_use', { calculator_type: brick ? 'brick' : 'construction_cost' });
    };
    if (calculator) {
        calculator.querySelector('[data-calculate]').addEventListener('click', () => calculate(true));
        calculator.addEventListener('input', (e) => {
            if (e.target.matches('input:not([type="checkbox"])')) calculate(false);
        });
        if (calculator.dataset.calculator === 'brick') {
            const syncType = () => {
                const type = calculator.querySelector('[data-brick-type]');
                const option = type.selectedOptions[0];
                const custom = type.value === 'custom';
                const bond = calculator.querySelector('[data-brick-bond]');
                const consumption = calculator.querySelector('[data-brick-size]');
                calculator.querySelector('[data-brick-bond-label]').hidden = custom;
                bond.disabled = custom;
                consumption.readOnly = !custom;
                if (!custom) {
                    bond.options[0].textContent = `Dik örüm — ${option.dataset.thickness} cm duvar`;
                    bond.options[1].textContent = `Yatık örüm — ${option.dataset.alternateThickness} cm duvar`;
                    consumption.value = bond.value === 'alternate' ? option.dataset.alternateRate : option.dataset.rate;
                }
                calculator.querySelector('[data-brick-consumption-note]').textContent = custom
                    ? 'Ürününüzün seçilen duvar kalınlığı için üretici tarafından belirtilen adet/m² değerini girin.'
                    : `Seçilen ürün ve örüm şekline göre ${consumption.value} adet/m² kullanılır. Farklı üretici değeri için “Diğer ürün” seçin.`;
            };
            const openings = calculator.querySelector('[data-brick-openings]');
            const renumberOpenings = () => {
                openings.querySelectorAll('.calc-opening').forEach((row, index) => {
                    row.querySelector('[data-opening-heading]').textContent = `${index + 1}. kapı / pencere`;
                    row.querySelector('[data-brick-remove-opening]').setAttribute('aria-label', `${index + 1}. kapı / pencereyi kaldır`);
                });
            };
            const addOpening = () => {
                openings.append(calculator.querySelector('[data-brick-opening-template]').content.cloneNode(true));
                renumberOpenings();
            };
            const syncMode = () => {
                const directArea = calculator.querySelector('[data-brick-mode]').value === 'area';
                calculator.querySelectorAll('[data-brick-dimensions]').forEach((label) => { label.hidden = directArea; });
                calculator.querySelector('[data-brick-net-area]').hidden = !directArea;
                calculator.querySelector('[data-brick-opening-controls]').hidden = directArea;
                const enabled = calculator.querySelector('[data-brick-has-openings]').checked;
                calculator.querySelector('#brick-openings').hidden = !enabled;
                if (enabled && !openings.children.length) addOpening();
            };
            calculator.addEventListener('change', (e) => {
                if (e.target.matches('[data-brick-type], [data-brick-bond]')) syncType();
                if (e.target.matches('[data-brick-mode], [data-brick-has-openings]')) syncMode();
                calculate(false);
            });
            calculator.addEventListener('click', (e) => {
                if (e.target.closest('[data-brick-add-opening]')) {
                    addOpening();
                    calculate(false);
                    openings.lastElementChild.querySelector('input').focus();
                }
                if (e.target.closest('[data-brick-remove-opening]')) {
                    const removed = e.target.closest('.calc-opening');
                    const nextFocus = removed.nextElementSibling?.querySelector('input')
                        || removed.previousElementSibling?.querySelector('input')
                        || calculator.querySelector('[data-brick-add-opening]');
                    removed.remove();
                    renumberOpenings();
                    if (!openings.children.length) {
                        calculator.querySelector('[data-brick-has-openings]').checked = false;
                        syncMode();
                        calculator.querySelector('[data-brick-has-openings]').focus();
                    } else nextFocus.focus();
                    calculate(false);
                }
            });
            syncType();
            syncMode();
            calculate(false);
        }
    }
})();
