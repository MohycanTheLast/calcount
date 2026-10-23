// Trvalé úložiště klíč–hodnota pro data zařízení (session, téma).
//
// Dnes localStorage. V nativní aplikaci (Capacitor) se vymění za bezpečné úložiště – to bývá asynchronní,
// proto bude potřeba při startu aplikace načíst hodnoty jednou do paměti a pak je číst synchronně tímto
// rozhraním. Pozor: inline skript v nuxt.config.ts (téma před načtením JS) čte localStorage napřímo.
export interface KeyValueStorage {
    get(key: string): string | null
    set(key: string, value: string): void
    remove(key: string): void
}

// Přístup ke storage může selhat (privátní režim, zakázaná data webu) – aplikace pak funguje bez něj
const browserStorage: KeyValueStorage = {
    get(key) {
        try {
            return localStorage.getItem(key)
        } catch {
            return null
        }
    },
    set(key, value) {
        try {
            localStorage.setItem(key, value)
        } catch {
            // ignore
        }
    },
    remove(key) {
        try {
            localStorage.removeItem(key)
        } catch {
            // ignore
        }
    },
}

export const appStorage: KeyValueStorage = browserStorage
