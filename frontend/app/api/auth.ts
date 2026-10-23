import { getApiBase } from '~/api/client'
import type { AuthTokens } from '~/api/types'

// Volání bez přístupového tokenu – používá je jen useAuth (přihlášení, obnova a zneplatnění session).
export const authApi = {
    login: (email: string, password: string) =>
        $fetch<AuthTokens>('/api/login', {
            baseURL: getApiBase(),
            method: 'POST',
            body: { email, password },
        }),

    refresh: (refreshToken: string) =>
        $fetch<AuthTokens>('/api/token/refresh', {
            baseURL: getApiBase(),
            method: 'POST',
            body: { refresh_token: refreshToken },
        }),

    invalidate: (refreshToken: string) =>
        $fetch('/api/token/invalidate', {
            baseURL: getApiBase(),
            method: 'POST',
            body: { refresh_token: refreshToken },
        }),
}
