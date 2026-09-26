const prefix = 'mati:commercial-intent:';

export function operationToken(): string {
    const key = prefix + window.location.pathname;
    try {
        const existing = sessionStorage.getItem(key);
        if (existing) return existing;
    } catch { /* Private storage may be unavailable; the form still retains its UUID. */ }
    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
    const id = `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
    try { sessionStorage.setItem(key, id); } catch { /* Retained in form state. */ }
    return id;
}

export function renewOperation(id: string): string {
    completeOperation(id);
    return operationToken();
}

export function completeOperation(id: string): void {
    try {
        for (const key of Object.keys(sessionStorage)) {
            if (key.startsWith(prefix) && sessionStorage.getItem(key) === id) sessionStorage.removeItem(key);
        }
    } catch { /* No persisted intent to remove. */ }
}
