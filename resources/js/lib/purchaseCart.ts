export type PurchaseCartItem = {
    variantId: number;
    productId: number;
    slug: string;
    name: string;
    brand: string;
    image: string;
    color: string;
    size: string;
    sku: string;
    price: number;
    quantity: number;
    stock: number;
};

export const PURCHASE_CART_KEY = 'mati_boutique_purchase_cart';
export const PURCHASE_CART_EVENT = 'mati-boutique:purchase-cart-updated';

function isBrowser() {
    return typeof window !== 'undefined';
}

export function readPurchaseCart(): PurchaseCartItem[] {
    if (!isBrowser()) {
        return [];
    }

    try {
        const raw = window.localStorage.getItem(PURCHASE_CART_KEY);
        const parsed = raw ? JSON.parse(raw) : [];

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

export function writePurchaseCart(items: PurchaseCartItem[]): void {
    if (!isBrowser()) {
        return;
    }

    window.localStorage.setItem(PURCHASE_CART_KEY, JSON.stringify(items));
    window.dispatchEvent(new CustomEvent(PURCHASE_CART_EVENT, { detail: items }));
}

export function clearPurchaseCart(): void {
    writePurchaseCart([]);
}

export function purchaseQuantityError(value: string | number, stock: number): string | null {
    return /^\d+$/.test(String(value)) && Number.isSafeInteger(Number(value)) && Number(value) >= 1 && Number(value) <= stock
        ? null : `Ingresa una cantidad entera entre 1 y ${stock}.`;
}

export function addPurchaseCartItem(item: PurchaseCartItem): PurchaseCartItem[] {
    const items = readPurchaseCart();
    const index = items.findIndex((current) => current.variantId === item.variantId);

    const quantity = item.quantity + (index >= 0 ? items[index].quantity : 0);
    const error = purchaseQuantityError(item.quantity, item.stock) ?? purchaseQuantityError(quantity, item.stock);
    if (error) throw new RangeError(`La cantidad solicitada, incluyendo el carrito, supera el stock o no es válida. ${error}`);

    if (index >= 0) {
        const current = items[index];
        items[index] = {
            ...current,
            ...item,
            quantity,
        };
    } else {
        items.push({
            ...item,
            quantity,
        });
    }

    writePurchaseCart(items);

    return items;
}

export function purchaseCartCount(items = readPurchaseCart()): number {
    return items.reduce((total, item) => total + item.quantity, 0);
}

export function purchaseCartTotal(items = readPurchaseCart()): number {
    return items.reduce((total, item) => total + item.price * item.quantity, 0);
}
