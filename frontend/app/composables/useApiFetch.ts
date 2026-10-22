type ApiFetchOptions = {
    method?: string
    headers?: Record<string, string>
    body?: any
    query?: Record<string, any>
}

export const useApiFetch = () => {
    const { getValidAccessToken, refreshAccessToken, accessToken, signOut } = useAuth()

    const apiFetch = async <T>(url: string, options: ApiFetchOptions = {}) => {
        const token = await getValidAccessToken()

        try {
            return await $fetch<T>(url, {
                ...options,
                headers: {
                    ...(options.headers ?? {}),
                    Authorization: `Bearer ${token}`,
                },
            } as any)
        } catch (e: any) {
            if (e?.status === 401) {
                // Token mohl expirovat v průběhu požadavku – zkusíme refresh a opakování
                try {
                    await refreshAccessToken()
                    return await $fetch<T>(url, {
                        ...options,
                        headers: {
                            ...(options.headers ?? {}),
                            Authorization: `Bearer ${accessToken.value}`,
                        },
                    } as any)
                } catch {
                    await signOut()
                }
            }
            throw e
        }
    }

    return { apiFetch }
}