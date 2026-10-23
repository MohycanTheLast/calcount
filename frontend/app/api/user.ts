import type { CurrentUser } from '~/api/types'

// Přihlášený uživatel a stav jeho účtu
export const useUserApi = () => {
    const { apiFetch } = useApiFetch()

    return {
        me: () => apiFetch<CurrentUser>('/api/user/me'),

        // Průvodce dokončený nebo přeskočený – na žádném zařízení se už sám nespustí
        completeOnboarding: () => apiFetch<void>('/api/user/onboarding', { method: 'POST' }),
    }
}
