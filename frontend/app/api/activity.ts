import type { CreatedPersonalAccessToken, DaySummary, PersonalAccessToken } from '~/api/types'

// Aktivní kalorie: ruční úprava dne a osobní tokeny pro zápis z iOS Zkratek
export const useActivityApi = () => {
    const { apiFetch } = useApiFetch()

    return {
        // Ruční hodnota má přednost před synchronizací i průměrnou aktivitou; vrací přepočítaný souhrn dne
        setManual: (date: string, activeKcal: number) =>
            apiFetch<DaySummary>(`/api/activity/manual/${date}`, { method: 'PUT', body: { activeKcal } }),

        // Zruší ruční hodnotu – den se zase počítá podle zdroje z nastavení
        deleteManual: (date: string) =>
            apiFetch<DaySummary>(`/api/activity/manual/${date}`, { method: 'DELETE' }),

        listTokens: () =>
            apiFetch<PersonalAccessToken[]>('/api/tokens'),

        createToken: (name: string) =>
            apiFetch<CreatedPersonalAccessToken>('/api/tokens', { method: 'POST', body: { name } }),

        revokeToken: (id: number) =>
            apiFetch<void>(`/api/tokens/${id}`, { method: 'DELETE' }),
    }
}
