import type { UserSettings } from '~/api/types'

export const useSettingsApi = () => {
    const { apiFetch } = useApiFetch()

    return {
        get: () => apiFetch<Partial<UserSettings>>('/api/user/settings'),

        save: (settings: UserSettings) =>
            apiFetch<void>('/api/user/settings', { method: 'POST', body: settings }),
    }
}
