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

export function addPurchaseCartItem(item: PurchaseCartItem): PurchaseCartItem[] {
    const items = readPurchaseCart();
    const index = items.findIndex((current) => current.variantId === item.variantId);

    if (index >= 0) {
        const current = items[index];
        items[index] = {
            ...current,
            ...item,
            quantity: Math.min(current.quantity + item.quantity, item.stock),
        };
    } else {
        items.push({
            ...item,
            quantity: Math.min(Math.max(1, item.quantity), item.stock),
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
