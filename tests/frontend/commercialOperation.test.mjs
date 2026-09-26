import { strict as assert } from 'node:assert';
import { test } from 'node:test';
import { operationToken, completeOperation, renewOperation } from '../../resources/js/lib/commercialOperation.ts';

test('an intent survives reload and is rotated only after confirmed success', () => {
    const storage = {};
    Object.defineProperties(storage, {
        getItem: { value: key => storage[key] ?? null },
        setItem: { value: (key, value) => { storage[key] = value; } },
        removeItem: { value: key => { delete storage[key]; } },
    });
    globalThis.window = { location: { pathname: '/bolsa/pago' } };
    globalThis.sessionStorage = storage;
    const id = operationToken();
    assert.match(id, /^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}$/);
    assert.equal(operationToken(), id);
    const renewed = renewOperation(id);
    assert.notEqual(renewed, id);
    assert.equal(operationToken(), renewed);
    window.location.pathname = '/cuenta/reservas';
    completeOperation(id);
    window.location.pathname = '/bolsa/pago';
    assert.notEqual(operationToken(), id);
    delete globalThis.window;
    delete globalThis.sessionStorage;
});
