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

    const isAccessTokenValid = computed(() => {
        if (!accessToken.value) return false
        if (!expiresAtMs.value) return false
        return Date.now() < (expiresAtMs.value - 30_000)
    })

    const isAuthenticated = computed(() => {
        // pro „přihlášený stav“ nám stačí, že máme refresh token
        // (access token může být zrovna prošlý, ale dá se obnovit)
        return !!refreshToken.value
    })

    const loadFromStorage = () => {
        if (!import.meta.client) return
        const raw = localStorage.getItem('cc_auth')
        if (!raw) return
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
            localStorage.removeItem('cc_auth')
            return
        }

        const session: AuthSession = {
            accessToken: accessToken.value ?? '',
            refreshToken: refreshToken.value,
            expiresAtMs: expiresAtMs.value ?? 0,
            user: user.value,
        }

        localStorage.setItem('cc_auth', JSON.stringify({ session }))
    }

    watch([accessToken, refreshToken, expiresAtMs, user], persistToStorage, { deep: true })

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
    }

    const signInWithEmail = async (email: string, password: string) => {
        // Backend: { access_token, refresh_token, expires_in }
        // Volitelně může posílat i user (pokud máš).
        const res = await $fetch<{
            access_token: string
            refresh_token: string
            expires_in: number
            user?: AuthUser
        }>('/api/login', {
            method: 'POST',
            body: { email, password },
        })

        setTokensFromBackend(res)
    }

    const refreshAccessToken = async () => {
        if (!refreshToken.value) throw new Error('Missing refresh token')

        if (refreshInFlight.value) {
            await refreshInFlight.value
            return
        }

        refreshInFlight.value = (async () => {
            const res = await $fetch<{
                access_token: string
                refresh_token: string
                expires_in: number
            }>('/api/token/refresh', {
                method: 'POST',
                body: { refresh_token: refreshToken.value },
            })

            setTokensFromBackend({
                ...res,
                refresh_token: res.refresh_token ?? refreshToken.value,
            })
        })()

        try {
            await refreshInFlight.value
        } finally {
            refreshInFlight.value = null
        }
    }

    const getValidAccessToken = async () => {
        if (isAccessTokenValid.value) return accessToken.value
        await refreshAccessToken()
        return accessToken.value
    }

    const signOut = async () => {
        // nejdřív si vezmeme aktuální refresh token, pak teprve mažeme state
        const rt = refreshToken.value

        try {
            if (rt) {
                await $fetch('/api/token/invalidate', {
                    method: 'POST',
                    body: { refresh_token: rt },
                })
            }
        } catch {
            // záměrně ignorujeme – i když invalidate selže, chceme uživatele lokálně odhlásit
        } finally {
            accessToken.value = null
            refreshToken.value = null
            expiresAtMs.value = null
            user.value = null
            await navigateTo('/')
        }
    }

    return {
        user,
        accessToken,
        refreshToken,
        expiresAtMs,
        isAuthenticated,
        isAccessTokenValid,
        loadFromStorage,
        signInWithEmail,
        refreshAccessToken,
        getValidAccessToken,
        signOut,
    }
}