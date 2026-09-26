export function decimalAmount(value: string): number {
    return /^\d+(\.\d{1,2})?$/.test(value) && Number.isFinite(Number(value))
        ? Number(value) : NaN;
}

export function reservationPayment(value: string, total: number) {
    const maximumPayment = Math.round(total * 100) / 100;
    const minimumPayment = Math.round(maximumPayment * 50) / 100;
    const amount = decimalAmount(value);
    const isPaymentValid = Number.isFinite(amount) && amount > 0
        && amount >= minimumPayment && amount <= maximumPayment;
    return {
        minimumPayment, maximumPayment, isPaymentValid,
        message: isPaymentValid ? null : `Ingresa un anticipo entre Bs ${minimumPayment.toFixed(2)} y Bs ${maximumPayment.toFixed(2)}, mayor a cero y con hasta dos decimales.`,
    };
}

export function discountError(value: string, subtotal: number): string | null {
    const amount = decimalAmount(value);
    return Number.isFinite(amount) && amount >= 0 && amount <= Math.round(subtotal * 100) / 100
        ? null : `El descuento debe estar entre 0 y Bs ${subtotal.toFixed(2)}, con hasta dos decimales.`;
}

export function commercialItemErrors(
    items: { variantId: string; quantity: string }[],
    variants: Map<string, { stock: number; hasInventory: boolean }>,
): (string | null)[] {
    const quantities = new Map<string, number>();
    items.forEach(({ variantId, quantity }) => quantities.set(variantId, (quantities.get(variantId) ?? 0) + Number(quantity)));
    return items.map(({ variantId, quantity }) => {
        const variant = variants.get(variantId);
        if (!variant) return 'Selecciona una variante.';
        if (!/^\d+$/.test(quantity) || !Number.isSafeInteger(Number(quantity)) || Number(quantity) < 1) {
            return 'Ingresa una cantidad entera mayor a cero.';
        }
        if (!variant.hasInventory || (quantities.get(variantId) ?? 0) > variant.stock) {
            return 'La cantidad total de esta variante supera el stock disponible.';
        }
        return null;
    });
}

export function voucherError(file: File | null): string | null {
    if (!file) return 'Sube el comprobante de pago.';
    return ['image/jpeg', 'image/png', 'image/webp'].includes(file.type) && file.size <= 4096 * 1024
        ? null : 'El comprobante debe ser JPG, PNG o WEBP y pesar como máximo 4 MB.';
}

export function damagedProductNameError(value: string): string | null {
    const normalized = value.trim().replace(/\s+/g, ' ');
    return normalized.length <= 180 && /^(?=.*\p{L})[\p{L}\p{M}\p{N} .,&'"()/+%®™-]+$/u.test(normalized)
        ? null : 'Ingresa un nombre de prenda válido, con letras y hasta 180 caracteres.';
}
