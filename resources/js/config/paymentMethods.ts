export const PAYMENT_METHODS = {
    cash: {
        value: 'efectivo',
        label: 'Efectivo',
        instructions: 'Pago recibido en caja de la tienda.',
    },
    qr: {
        value: 'qr',
        label: 'QR',
        qrUrl: '/images/payments/qr-mati-boutique.png',
        instructions: 'Escanea el QR y sube el comprobante de pago.',
    },
    bankTransfer: {
        value: 'transferencia_bancaria',
        label: 'Transferencia bancaria',
        accountLabel: 'Cuenta bancaria Mati Boutique',
        accountNumber: '1003383438',
        accountHolder: 'LIMBER SOTO CAZU',
        instructions: 'Realiza la transferencia bancaria y sube el comprobante de pago.',
    },
} as const;

export const ONLINE_PAYMENT_METHOD_OPTIONS = [
    { label: PAYMENT_METHODS.qr.label, value: PAYMENT_METHODS.qr.value },
    { label: PAYMENT_METHODS.bankTransfer.label, value: PAYMENT_METHODS.bankTransfer.value },
];
