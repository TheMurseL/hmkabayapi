(function () {
    'use strict';

    const roundMoney = (value) => Math.round((value + Number.EPSILON) * 100) / 100;

    function calculateLabor(input) {
        let project = null;
        const reject = (field, message) => { throw { field, message }; };
        const read = (field, minimum, maximum, label, integer = false) => {
            const raw = input[field];
            const value = (typeof raw === 'number' || (typeof raw === 'string' && raw.trim() !== '')) ? Number(raw) : NaN;
            if (!Number.isFinite(value) || value < minimum || value > maximum || (integer && !Number.isInteger(value))) {
                reject(field, label);
            }
            return value;
        };
        try {
            if (!['building', 'manual'].includes(input.mode)) reject('mode', 'Alan giriş yöntemini seçin.');
            if (input.mode === 'manual') {
                project = { area: read('area', 0.01, 1000000, 'Toplam bina alanını 0 ile 1.000.000 m² arasında, sıfırdan büyük girin.'), apartments: null, floors: null };
            } else {
                const apartments = read('apartments', 1, 10000, 'Daire sayısını 1 ile 10.000 arasında tam sayı olarak girin.', true);
                const perFloor = read('perFloor', 1, 1000, 'Kat başına daire sayısını 1 ile 1.000 arasında tam sayı olarak girin.', true);
                const apartmentArea = read('apartmentArea', 0.01, 1000000, 'Bir dairenin net alanını sıfırdan büyük girin.');
                const commonArea = read('commonArea', 0, 1000000, 'Ortak alan toplamını girin. Ortak alan yoksa 0 yazın.');
                const basementArea = input.basementEnabled
                    ? read('basementArea', 0.01, 1000000, 'Bodrum / otopark alanını sıfırdan büyük girin.') : 0;
                const area = Number((apartments * apartmentArea + commonArea + basementArea).toPrecision(12));
                if (area > 1000000) reject('area', 'Hesaba esas toplam alan en fazla 1.000.000 m² olabilir. Daire ve ek alan bilgilerini kontrol edin.');
                project = { area, apartments, floors: Math.ceil(apartments / perFloor) };
            }
            if (!['building', 'surface'].includes(input.formworkBasis)) reject('formworkBasis', 'Kalıp işçiliğinin metraj türünü seçin.');
            const formworkQuantity = input.formworkBasis === 'surface'
                ? read('formworkArea', 0.01, 1000000, 'Kalıp yüzey alanını projedeki metraja göre, sıfırdan büyük girin.') : project.area;
            const formworkRate = read('formworkRate', 0.01, 1000000, 'Kalıp işçilik birim fiyatını sıfırdan büyük ve en fazla 1.000.000 ₺ olarak girin.');
            const formworkCost = roundMoney(formworkQuantity * formworkRate);
            let ironQuantity = 0, ironCost = 0;
            if (input.ironEnabled) {
                if (!['area', 'tonne'].includes(input.ironBasis)) reject('ironBasis', 'Demir işçiliğinin fiyatlandırma birimini seçin.');
                ironQuantity = input.ironBasis === 'tonne'
                    ? read('ironTonnes', 0.001, 100000, 'Demir tonajını projeye göre, sıfırdan büyük girin.') : project.area;
                const ironRate = read('ironRate', 0.01, 1000000, 'Demir işçilik birim fiyatını sıfırdan büyük ve en fazla 1.000.000 ₺ olarak girin.');
                ironCost = roundMoney(ironQuantity * ironRate);
            }
            const subtotal = roundMoney(formworkCost + ironCost);
            const vatRate = input.vatEnabled ? read('vatRate', 0, 100, 'KDV oranını %0 ile %100 arasında girin.') : 0;
            const vat = roundMoney(subtotal * vatRate / 100);
            const total = roundMoney(subtotal + vat);
            return { valid: true, project, formworkQuantity, formworkCost, ironQuantity, ironCost, subtotal, vat, total,
                perSquareMetre: roundMoney(total / project.area),
                perApartment: project.apartments ? roundMoney(total / project.apartments) : null };
        } catch (error) {
            if (!error.field) throw error;
            return { valid: false, field: error.field, message: error.message, project };
        }
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = { calculateLabor };
    if (typeof document === 'undefined') return;

    const root = document.querySelector('[data-labor-calculator]');
    if (!root) return;
    const form = root.querySelector('[data-labor-form]');
    const field = (name) => form.elements.namedItem(name);
    const out = (name, value) => { root.querySelector(`[data-labor-out="${name}"]`).textContent = value; };
    const decimal = (value) => new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 3 }).format(value);
    const money = (value) => new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 2 }).format(value) + ' ₺';
    const errorBox = root.querySelector('[data-labor-error]');
    const quote = root.querySelector('[data-labor-quote]');
    const baseQuote = quote.href;
    let submitted = false;

    const readInput = () => Object.fromEntries(Array.from(form.elements)
        .filter((control) => control.name)
        .map((control) => [control.name, control.type === 'checkbox' ? control.checked : control.value]));

    const syncControls = () => {
        const show = (name, enabled) => {
            const group = root.querySelector(`[data-labor-group="${name}"]`);
            group.hidden = !enabled;
            group.querySelectorAll('input, select').forEach((control) => { control.disabled = !enabled; });
        };
        const building = field('mode').value === 'building';
        show('building', building);
        show('manual', !building);
        show('basement', building && field('basementEnabled').checked);
        show('surface', field('formworkBasis').value === 'surface');
        show('iron', field('ironEnabled').checked);
        show('tonnes', field('ironEnabled').checked && field('ironBasis').value === 'tonne');
        show('vat', field('vatEnabled').checked);
        root.querySelector('[data-labor-floor-fact]').hidden = !building;
        root.querySelector('[data-labor-apartment-fact]').hidden = !building;
        root.querySelector('[data-labor-iron-line]').hidden = !field('ironEnabled').checked;
        root.querySelector('[data-labor-formwork-label]').textContent = field('formworkBasis').value === 'surface'
            ? 'Kalıp işçilik fiyatı (₺/m² kalıp yüzeyi)' : 'Kalıp işçilik fiyatı (₺/m² bina alanı)';
        root.querySelector('[data-labor-iron-label]').textContent = field('ironBasis').value === 'tonne'
            ? 'Demir işçilik fiyatı (₺/ton)' : 'Demir işçilik fiyatı (₺/m² bina alanı)';
        root.querySelector('[data-labor-vat-label]').textContent = field('vatEnabled').checked
            ? 'Eklenecek KDV' : 'KDV';
    };

    // A price in ₺/ton or ₺/surface-m² must not silently become a price in ₺/building-m².
    const clearChangedRate = (name) => {
        if (name === 'formworkBasis') field('formworkRate').value = '';
        if (name === 'ironBasis') field('ironRate').value = '';
    };

    const update = (track = false) => {
        syncControls();
        const input = readInput();
        const result = calculateLabor(input);
        form.querySelectorAll('[aria-invalid]').forEach((control) => {
            control.removeAttribute('aria-invalid');
            control.removeAttribute('aria-describedby');
        });
        errorBox.hidden = !submitted || result.valid;
        errorBox.textContent = result.valid ? '' : result.message;
        if (submitted && !result.valid) {
            const requested = field(result.field);
            const invalid = requested && !requested.disabled ? requested : field('apartmentArea');
            invalid.setAttribute('aria-invalid', 'true');
            invalid.setAttribute('aria-describedby', 'labor-error');
            if (track) invalid.focus();
        }
        out('area', result.project ? decimal(result.project.area) + ' m²' : '—');
        out('building', result.project?.apartments ? `${decimal(result.project.floors)} kat / ${decimal(result.project.apartments)} daire` : '—');
        const quoteUrl = new URL(baseQuote);
        if (!result.valid) {
            for (const name of ['formworkCost', 'ironCost', 'subtotal', 'total', 'perSquareMetre', 'perApartment']) out(name, '—');
            out('formworkDetail', 'Metraj ve birim fiyat bekleniyor.');
            out('ironDetail', 'Metraj ve birim fiyat bekleniyor.');
            out('vat', input.vatEnabled ? '—' : 'Eklenmedi');
            out('status', submitted ? 'Bilgileri kontrol ederek yeniden hesaplayın.' : 'Toplamı görmek için birim fiyatları girin.');
        } else {
            for (const name of ['formworkCost', 'ironCost', 'subtotal', 'total', 'perSquareMetre', 'perApartment']) {
                out(name, result[name] === null ? '—' : money(result[name]));
            }
            const formworkUnit = input.formworkBasis === 'surface' ? 'm² kalıp yüzeyi' : 'm² bina alanı';
            const ironUnit = input.ironBasis === 'tonne' ? 'ton' : 'm² bina alanı';
            out('formworkDetail', `${decimal(result.formworkQuantity)} ${formworkUnit} × ${money(Number(input.formworkRate))}`);
            out('ironDetail', `${decimal(result.ironQuantity)} ${ironUnit} × ${money(Number(input.ironRate))}`);
            out('vat', input.vatEnabled ? money(result.vat) + ` (%${decimal(Number(input.vatRate))})` : 'Eklenmedi');
            out('status', input.vatEnabled ? 'Seçtiğiniz oranda KDV eklendi. Yalnızca işçilik bedelidir.' : 'KDV ayrıca eklenmedi. Yalnızca işçilik bedelidir.');
            quoteUrl.searchParams.set('area', String(result.project.area));
            if (track) {
                document.dispatchEvent(new CustomEvent('site:measurement', { detail: {
                    name: 'calculator_use', parameters: { calculator_type: 'construction_cost' }
                } }));
            }
        }
        quote.href = quoteUrl.href;
        return result;
    };

    errorBox.id = 'labor-error';
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitted = true;
        update(true);
    });
    form.addEventListener('input', (event) => {
        if (event.target.matches('input[type="number"]')) update();
    });
    form.addEventListener('change', (event) => {
        if (event.target.matches('select, input[type="checkbox"]')) {
            clearChangedRate(event.target.name);
            update();
        }
    });
    form.addEventListener('reset', () => {
        submitted = false;
        queueMicrotask(() => update());
    });
    update();
})();
