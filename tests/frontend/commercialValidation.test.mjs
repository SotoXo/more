import { strict as assert } from 'node:assert';
import { test } from 'node:test';
import { damagedProductNameError, commercialItemErrors, decimalAmount, discountError, reservationPayment, voucherError } from '../../resources/js/lib/commercialValidation.ts';

test('reservation deposit uses inclusive 50–100% bounds and rejects malformed values', () => {
    for (const value of ['74.99', '0', '-1', '150.01', '1e10', '12,34', 'NaN', '', ' ', 'abc', '123abc', '@@@', '999999999999']) {
        assert.equal(reservationPayment(value, 150).isPaymentValid, false, value);
    }
    for (const value of ['75', '75.00', '150', '000075']) {
        const result = reservationPayment(value, 150);
        assert.equal(result.isPaymentValid, true, value);
        assert.equal(result.minimumPayment, 75);
        assert.equal(result.maximumPayment, 150);
    }
    assert.equal(reservationPayment('75.00', 150.01).isPaymentValid, false);
    assert.equal(reservationPayment('75.01', 150.01).isPaymentValid, true);
});

test('discount is validated without silently truncating it', () => {
    for (const value of ['0', '150', '12.34', '000001']) assert.equal(discountError(value, 150), null);
    for (const value of ['-1', '999999', '150.01', '1e10', '12,34', '', 'abc']) assert.ok(discountError(value, 150));
    assert.equal(decimalAmount('999999'), 999999);
    assert.equal(discountError('0.87', 0.29 * 3), null);
});

test('quantities account for duplicate rows and available inventory', () => {
    const variants = new Map([['1', { stock: 3, hasInventory: true }]]);
    assert.deepEqual(commercialItemErrors([{ variantId: '1', quantity: '3' }], variants), [null]);
    for (const quantity of ['0', '-1', '', '1e10', '1.5', '4', '123abc']) {
        assert.ok(commercialItemErrors([{ variantId: '1', quantity }], variants)[0]);
    }
    assert.ok(commercialItemErrors([{ variantId: 'missing', quantity: '1' }], variants)[0]);
    assert.ok(commercialItemErrors([{ variantId: '1', quantity: '2' }, { variantId: '1', quantity: '2' }], variants).every(Boolean));
});

test('voucher must be an allowed image within the backend limit', () => {
    assert.ok(voucherError(null));
    assert.ok(voucherError({ type: 'application/pdf', size: 100 }));
    assert.ok(voucherError({ type: 'image/jpeg', size: 4096 * 1024 + 1 }));
    assert.equal(voucherError({ type: 'image/png', size: 4096 * 1024 }), null);
});


test('commercial names accept Spanish names and normal punctuation, reject garbage', () => {
    assert.equal(damagedProductNameError('   Polo Niño 100% algodón - L   '), null);
    for (const value of ['@@@', '', '   ', '123', '<script>alert(1)</script>']) assert.ok(damagedProductNameError(value));
});
