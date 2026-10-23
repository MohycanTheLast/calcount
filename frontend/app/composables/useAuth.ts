import { authApi } from '~/api/auth'
import { appStorage } from '~/lib/storage'

const AUTH_STORAGE_KEY = 'cc_auth'

export type AuthUser = {
    id: string
    email?: string
    name?: string
}

type AuthSession = {
    accessToken: string
    refreshToken: string
    expiresAtMs: number // absolutní čas v ms
    user?: AuthUser | null
}

export const useAuth = () => {
    const user = useState<AuthUser | null>('auth:user', () => null)

    const accessToken = useState<string | null>('auth:access_token', () => null)
    const refreshToken = useState<string | null>('auth:refresh_token', () => null)
    const expiresAtMs = useState<number | null>('auth:expires_at_ms', () => null)

    const refreshInFlight = useState<Promise<void> | null>('auth:refresh_in_flight', () => null)

    // Záměrně funkce, ne computed – computed by si výsledek zapamatoval (Date.now() není reaktivní)
    // a prošlý token by dál hlásil jako platný.
    const hasValidAccessToken = () => {
        if (!accessToken.value) return false
        if (!expiresAtMs.value) return false
        return Date.now() < (expiresAtMs.value - 30_000)
    }

    const isAuthenticated = computed(() => {
        // pro „přihlášený stav“ nám stačí, že máme refresh token
        // (access token může být zrovna prošlý, ale dá se obnovit)
        return !!refreshToken.value
    })

    const loadFromStorage = () => {
        if (!import.meta.client) return
        const raw = appStorage.get(AUTH_STORAGE_KEY)
        if (!raw) {
            // Paměť a storage se ukládají společně, chybějící záznam = odhlášení (např. v jiné záložce)
            accessToken.value = null
            refreshToken.value = null
            expiresAtMs.value = null
            user.value = null
            return
        }
        try {
            const parsed = JSON.parse(raw) as { session: AuthSession }
            const s = parsed.session
            accessToken.value = s.accessToken ?? null
            refreshToken.value = s.refreshToken ?? null
            expiresAtMs.value = typeof s.expiresAtMs === 'number' ? s.expiresAtMs : null
            user.value = s.user ?? null
        } catch {
            // ignore
        }
    }

    const persistToStorage = () => {
        if (!import.meta.client) return

        if (!refreshToken.value) {
            appStorage.remove(AUTH_STORAGE_KEY)
            return
        }

        const session: AuthSession = {
            accessToken: accessToken.value ?? '',
            refreshToken: refreshToken.value,
            expiresAtMs: expiresAtMs.value ?? 0,
            user: user.value,
        }

        appStorage.set(AUTH_STORAGE_KEY, JSON.stringify({ session }))
    }

    const clearSession = () => {
        accessToken.value = null
        refreshToken.value = null
        expiresAtMs.value = null
        user.value = null
        persistToStorage()

        // Data předchozího uživatele nesmí zůstat v paměti pro dalšího (deník, nastavení).
        // Téma zůstává – patří zařízení a je uložené v localStorage.
        useNuxtApp().$queryClient.clear()
        useState('user:settings:loaded').value = false
    }

    const setTokensFromBackend = (payload: {
        access_token: string
        refresh_token: string
        expires_in: number
        user?: AuthUser
    }) => {
        accessToken.value = payload.access_token
        refreshToken.value = payload.refresh_token
        expiresAtMs.value = Date.now() + payload.expires_in * 1000
        if (payload.user) user.value = payload.user
        persistToStorage()
    }

    const signInWithEmail = async (email: string, password: string) => {
        setTokensFromBackend(await authApi.login(email, password))
    }

    // Při odmítnutí refresh tokenu serverem session smaže; při síťové chybě ji nechá a jen vyhodí chybu.
    // Volající pozná ukončenou session podle isAuthenticated.
    const refreshAccessToken = async () => {
        if (refreshInFlight.value) {
            await refreshInFlight.value
            return
        }

        refreshInFlight.value = (async () => {
            // Refresh token je jednorázový – jiná záložka ho mohla mezitím vyměnit,
            // proto nejdřív načteme aktuální stav ze storage.
            const previousAccessToken = accessToken.value
            loadFromStorage()
            if (accessToken.value !== previousAccessToken && hasValidAccessToken()) return

            if (!refreshToken.value) throw new Error('Missing refresh token')

            try {
                const res = await authApi.refresh(refreshToken.value)

                setTokensFromBackend({
                    ...res,
                    refresh_token: res.refresh_token ?? refreshToken.value,
                })
            } catch (e: any) {
                // Session ukončíme při jakékoli odpovědi serveru (neplatný refresh token, chyba podpisu
                // JWT po změně klíčů/passphrase apod.). Necháme ji jen při síťové chybě (bez statusu)
                // a při dočasné nedostupnosti serveru (502–504), kdy má smysl to zkusit znovu.
                const status = e?.status
                if (status && ![502, 503, 504].includes(status)) clearSession()
                throw e
            }
        })()

        try {
            await refreshInFlight.value
        } finally {
            refreshInFlight.value = null
        }
    }

    const getValidAccessToken = async () => {
        if (hasValidAccessToken()) return accessToken.value
        await refreshAccessToken()
        return accessToken.value
    }

    const signOut = async () => {
        // nejdřív si vezmeme aktuální refresh token, pak teprve mažeme state
        const rt = refreshToken.value

        try {
            if (rt) {
                await authApi.invalidate(rt)
            }
        } catch {
            // záměrně ignorujeme – i když invalidate selže, chceme uživatele lokálně odhlásit
        } finally {
            clearSession()
            await navigateTo('/')
        }
    }

    return {
        user,
        accessToken,
        refreshToken,
        expiresAtMs,
        isAuthenticated,
        hasValidAccessToken,
        loadFromStorage,
        signInWithEmail,
        refreshAccessToken,
        getValidAccessToken,
        signOut,
    }
}