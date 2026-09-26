import { strict as assert } from 'node:assert';
import { test } from 'node:test';
import { addPurchaseCartItem, purchaseQuantityError, readPurchaseCart } from '../../resources/js/lib/purchaseCart.ts';

test('cart quantity rejects invalid values without silently replacing or truncating them', () => {
    for (const value of ['-1', '0', '', ' ', '1e10', '12.34', '12,34', 'abc', '123abc', '@@@', '999999999999', '4']) {
        assert.ok(purchaseQuantityError(value, 3), value);
    }
    assert.equal(purchaseQuantityError('000001', 3), null);
    assert.equal(purchaseQuantityError('3', 3), null);
    let saved = null;
    globalThis.window = { localStorage: { getItem: () => saved, setItem: (_, value) => { saved = value; } }, dispatchEvent: () => {} };
    try {
        addPurchaseCartItem({ variantId: 1, quantity: 2, stock: 3 });
        assert.throws(() => addPurchaseCartItem({ variantId: 1, quantity: 2, stock: 3 }), RangeError);
        assert.equal(readPurchaseCart()[0].quantity, 2);
        addPurchaseCartItem({ variantId: 1, quantity: 1, stock: 3 });
        assert.equal(readPurchaseCart()[0].quantity, 3);
        assert.throws(() => addPurchaseCartItem({ variantId: 2, quantity: 0, stock: 3 }), RangeError);
        assert.equal(readPurchaseCart().length, 1);
    } finally { delete globalThis.window; }
});
