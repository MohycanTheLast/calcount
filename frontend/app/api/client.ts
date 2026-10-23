// Adresa backendu: '' = stejný origin (web servírovaný backendem, lokálně přes devProxy).
// V nativní aplikaci (Capacitor) bude absolutní URL – nastavuje se při buildu přes NUXT_PUBLIC_API_BASE.
// Pozn.: pro jiný origin bude potřeba CORS na backendu (nelmio/cors-bundle, backlog F11).
export const getApiBase = (): string => String(useRuntimeConfig().public.apiBase ?? '')
