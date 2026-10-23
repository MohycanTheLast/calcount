import { getApiBase } from '~/api/client'
import type { AuthTokens, RegisterResult } from '~/api/types'

// Volání bez přístupového tokenu – přihlášení, registrace, obnova a zneplatnění session.
export const authApi = {
    login: (email: string, password: string) =>
        $fetch<AuthTokens>('/api/login', {
            baseURL: getApiBase(),
            method: 'POST',
            body: { email, password },
        }),

    register: (email: string, password: string, termsAccepted: boolean) =>
        $fetch<RegisterResult>('/api/register', {
            baseURL: getApiBase(),
            method: 'POST',
            body: { email, password, termsAccepted },
        }),

    // Parametry z odkazu v registračním e-mailu
    verifyEmail: (params: { uid: string; expires: string; signature: string }) =>
        $fetch<{ status: 'verified' }>('/api/register/verify', {
            baseURL: getApiBase(),
            method: 'POST',
            body: params,
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
