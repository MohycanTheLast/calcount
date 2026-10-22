type ApiFetchOptions = {
    method?: string
    headers?: Record<string, string>
    body?: any
    query?: Record<string, any>
}

export const useApiFetch = () => {
    const { getValidAccessToken, refreshAccessToken, accessToken, isAuthenticated, signOut } = useAuth()

    const request = <T>(url: string, options: ApiFetchOptions, token: string | null) =>
        $fetch<T>(url, {
            ...options,
            headers: {
                ...(options.headers ?? {}),
                Authorization: `Bearer ${token}`,
            },
        } as any)

    // Odhlásí jen tehdy, když server odmítl refresh token (session opravdu skončila).
    // Síťová chyba nebo chyba samotného požadavku uživatele neodhlásí.
    const signOutIfSessionEnded = async () => {
        if (!isAuthenticated.value) await signOut()
    }

    const apiFetch = async <T>(url: string, options: ApiFetchOptions = {}) => {
        let token: string | null

        try {
            token = await getValidAccessToken()
        } catch (e) {
            await signOutIfSessionEnded()
            throw e
        }

        try {
            return await request<T>(url, options, token)
        } catch (e: any) {
            if (e?.status !== 401) throw e
        }

        // Token mohl expirovat v průběhu požadavku – refresh a jeden nový pokus
        try {
            await refreshAccessToken()
        } catch (e) {
            await signOutIfSessionEnded()
            throw e
        }

        try {
            return await request<T>(url, options, accessToken.value)
        } catch (e: any) {
            // Server odmítá i čerstvě vydaný token (např. nesedí JWT klíče) – session je nepoužitelná
            if (e?.status === 401) await signOut()
            throw e
        }
    }

    return { apiFetch }
}
