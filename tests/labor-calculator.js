'use strict';

const assert = require('node:assert/strict');
const { calculateLabor } = require('../assets/js/labor-calculator.js');

let checks = 0;
const building = {
    mode: 'building',
    apartments: 8,
    perFloor: 2,
    apartmentArea: 100,
    commonArea: 120,
    basementEnabled: true,
    basementArea: 80,
    formworkBasis: 'building',
    formworkRate: 500,
    ironEnabled: true,
    ironBasis: 'area',
    ironRate: 200,
    vatEnabled: true,
    vatRate: 20,
};

function valid(changes = {}) {
    const result = calculateLabor({ ...building, ...changes });
    assert.equal(result.valid, true, result.message || 'Expected a valid estimate');
    checks++;
    return result;
}

function invalid(changes, field) {
    const result = calculateLabor({ ...building, ...changes });
    assert.equal(result.valid, false, `Expected ${field} to be rejected`);
    assert.equal(result.field, field);
    assert.equal(typeof result.message, 'string');
    assert.ok(result.message.trim(), 'Invalid results need an explanation');
    assert.ok(result.project === null || typeof result.project === 'object');
    assert.ok(!Object.hasOwn(result, 'total'), 'Invalid estimates must not retain a total');
    checks++;
    return result;
}

// Common and basement areas are totals, added once after the apartment areas.
const baseline = valid();
assert.deepEqual(baseline.project, { area: 1000, apartments: 8, floors: 4 });
assert.equal(baseline.formworkQuantity, 1000);
assert.equal(baseline.formworkCost, 500000);
assert.equal(baseline.ironQuantity, 1000);
assert.equal(baseline.ironCost, 200000);
assert.equal(baseline.subtotal, 700000);
assert.equal(baseline.vat, 140000);
assert.equal(baseline.total, 840000);
assert.equal(baseline.perSquareMetre, 840);
assert.equal(baseline.perApartment, 105000);

const partialFloor = valid({ apartments: 9, perFloor: 2, commonArea: 100, basementEnabled: false });
assert.deepEqual(partialFloor.project, { area: 1000, apartments: 9, floors: 5 });
assert.equal(partialFloor.total, baseline.total, 'A partial final floor cannot invent another apartment');
const changedOccupancy = valid({ perFloor: 3 });
assert.equal(changedOccupancy.project.floors, 3);
assert.equal(changedOccupancy.project.area, 1000);
assert.equal(changedOccupancy.total, baseline.total, 'Flats per floor changes floor count, not entered total area');

const manual = valid({ mode: 'manual', area: 1000 });
assert.deepEqual(manual.project, { area: 1000, apartments: null, floors: null });
for (const key of ['formworkCost', 'ironCost', 'subtotal', 'vat', 'total', 'perSquareMetre']) {
    assert.equal(manual[key], baseline[key], `Equivalent manual area: ${key}`);
}
assert.equal(manual.perApartment, null);

const mixed = valid({
    formworkBasis: 'surface', formworkArea: 3000, formworkRate: 350,
    ironBasis: 'tonne', ironTonnes: 25.5, ironRate: 9000,
});
assert.equal(mixed.project.area, 1000);
assert.equal(mixed.formworkQuantity, 3000);
assert.equal(mixed.formworkCost, 1050000);
assert.equal(mixed.ironQuantity, 25.5);
assert.equal(mixed.ironCost, 229500);
assert.equal(mixed.subtotal, 1279500);
assert.equal(mixed.vat, 255900);
assert.equal(mixed.total, 1535400);
assert.equal(mixed.perSquareMetre, 1535.4);
assert.equal(mixed.perApartment, 191925);

const strings = valid(Object.fromEntries(Object.entries(building).map(([key, value]) => [key, typeof value === 'number' ? String(value) : value])));
assert.deepEqual(strings, baseline, 'Numeric form strings and numbers give the same estimate');

const noVat = valid({ vatEnabled: false, vatRate: 'invalid' });
assert.equal(noVat.vat, 0);
assert.equal(noVat.total, 700000);
assert.equal(valid({ vatRate: 0 }).total, noVat.total);
assert.equal(valid({ vatRate: 100 }).total, 1400000);

const noIron = valid({ ironEnabled: false, ironBasis: 'invalid', ironRate: -200, ironTonnes: 'invalid' });
assert.equal(noIron.ironQuantity, 0);
assert.equal(noIron.ironCost, 0);
assert.equal(noIron.subtotal, 500000);
assert.equal(noIron.total, 600000);

// Hidden inputs from a previous mode must not affect validity or the result.
const hiddenManual = valid({
    mode: 'manual', area: 1000,
    apartments: -8, perFloor: 1.5, apartmentArea: 'invalid', commonArea: -1,
    basementEnabled: true, basementArea: 'invalid',
    formworkArea: -100, ironTonnes: 'invalid',
});
assert.deepEqual(hiddenManual, manual);
assert.equal(valid({ area: 'invalid', formworkArea: -1, ironTonnes: 'invalid' }).total, baseline.total);
const noBasement = valid({ basementEnabled: false, basementArea: 'invalid' });
assert.equal(noBasement.project.area, 920);
assert.equal(noBasement.total, 772800);
assert.equal(valid({ commonArea: 0, basementEnabled: false }).project.area, 800);

// Round each labor line before summing and round VAT independently.
const rounding = valid({
    mode: 'manual', area: 1,
    formworkRate: 1.005, ironRate: 2.005,
});
assert.equal(rounding.formworkCost, 1.01);
assert.equal(rounding.ironCost, 2.01);
assert.equal(rounding.subtotal, 3.02);
assert.equal(rounding.vat, 0.6);
assert.equal(rounding.total, 3.62);
const fractional = valid({ mode: 'manual', area: 0.1, formworkRate: 0.2, ironRate: 0.3, vatRate: 10 });
assert.equal(fractional.formworkCost, 0.02);
assert.equal(fractional.ironCost, 0.03);
assert.equal(fractional.subtotal, 0.05);
assert.equal(fractional.vat, 0.01);
assert.equal(fractional.total, 0.06);

// No formwork surface coefficient or rebar tonnage is inferred from floor area.
invalid({ formworkBasis: 'surface', formworkArea: '' }, 'formworkArea');
invalid({ formworkBasis: 'surface', formworkArea: 0 }, 'formworkArea');
invalid({ ironBasis: 'tonne', ironTonnes: '' }, 'ironTonnes');
invalid({ ironBasis: 'tonne', ironTonnes: 0 }, 'ironTonnes');
invalid({ ironBasis: 'tonne', ironTonnes: -1 }, 'ironTonnes');

for (const field of ['apartments', 'perFloor']) {
    for (const value of ['', 0, -1, 1.5, 'invalid', Infinity, NaN]) invalid({ [field]: value }, field);
}
for (const field of ['apartmentArea', 'formworkRate', 'ironRate']) {
    for (const value of ['', 0, -1, 'invalid', Infinity, NaN]) invalid({ [field]: value }, field);
}
for (const value of ['', -1, 'invalid', Infinity, NaN]) invalid({ commonArea: value }, 'commonArea');
for (const value of ['', 0, -1, 'invalid', Infinity, NaN]) invalid({ basementArea: value }, 'basementArea');
for (const value of ['', 0, -1, 'invalid', Infinity, NaN, 1000001]) invalid({ mode: 'manual', area: value }, 'area');
for (const value of ['', -1, 100.01, 'invalid', Infinity, NaN]) invalid({ vatRate: value }, 'vatRate');
invalid({ mode: 'invalid' }, 'mode');
invalid({ formworkBasis: 'invalid' }, 'formworkBasis');
invalid({ ironBasis: 'invalid' }, 'ironBasis');
invalid({ apartments: 10000, apartmentArea: 100, commonArea: 1, basementEnabled: false }, 'area');
assert.equal(valid({ mode: 'manual', area: 1000000 }).project.area, 1000000);

console.log(`Labor calculator: ${checks} arithmetic, mode, rounding and validation cases passed.`);
module.exports = { checks };
