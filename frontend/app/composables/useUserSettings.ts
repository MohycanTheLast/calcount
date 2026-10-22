export type ThemeMode = 'system' | 'light' | 'dark'

export type AiCommentMode =
    | 'none'
    | 'brief_health'
    | 'funny'
    | 'praise_or_criticize'
    | 'random'

export type UserSettings = {
    bmrKcal: number | null
    avgDailyActivityKcal: number | null
    themeMode: ThemeMode
    aiCommentMode: AiCommentMode
    weightKg: number | null
    saveWeight: boolean
}

const DEFAULT_SETTINGS: UserSettings = {
    bmrKcal: null,
    avgDailyActivityKcal: null,
    themeMode: 'system',
    aiCommentMode: 'none',
    weightKg: null,
    saveWeight: false,
}

export const useUserSettings = () => {
    const settings = useState<UserSettings>('user:settings', () => ({ ...DEFAULT_SETTINGS }))
    const loading = useState<boolean>('user:settings:loading', () => false)
    const saving = useState<boolean>('user:settings:saving', () => false)
    const error = useState<string | null>('user:settings:error', () => null)

    const applyTheme = () => {
        if (!import.meta.client) return

        const root = document.documentElement
        const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)')?.matches ?? false

        const effective =
            settings.value.themeMode === 'system'
                ? (prefersDark ? 'dark' : 'light')
                : settings.value.themeMode

        root.classList.toggle('dark', effective === 'dark')
    }

    // Reaguj na změny OS tématu, ale jen když je zvolený "system"
    if (import.meta.client) {
        const mql = window.matchMedia('(prefers-color-scheme: dark)')
        const onChange = () => {
            if (settings.value.themeMode === 'system') applyTheme()
        }

        mql.addEventListener?.('change', onChange)
        onBeforeUnmount(() => {
            mql.removeEventListener?.('change', onChange)
        })
    }

    const fetchFromApi = async () => {
        const { getValidAccessToken } = useAuth()
        loading.value = true
        error.value = null

        try {
            const token = await getValidAccessToken()

            const res = await $fetch<Partial<UserSettings>>('/api/user/settings', {
                method: 'GET',
                headers: {
                    Authorization: `Bearer ${token}`,
                },
            })

            settings.value = { ...DEFAULT_SETTINGS, ...res }
            applyTheme()
        } catch (e) {
            error.value = 'Nepodařilo se načíst nastavení.'
            // necháme defaulty (nebo poslední stav v paměti)
            applyTheme()
            throw e
        } finally {
            loading.value = false
        }
    }

    const saveToApi = async () => {
        const { getValidAccessToken } = useAuth()
        saving.value = true
        error.value = null

        try {
            const token = await getValidAccessToken()

            const payload: UserSettings = {
                ...settings.value,
                saveWeight: settings.value.saveWeight,
                weightKg: settings.value.saveWeight ? settings.value.weightKg : null,
            }

            await $fetch('/api/user/settings', {
                method: 'POST',
                headers: {
                    Authorization: `Bearer ${token}`,
                },
                body: payload,
            })

            applyTheme()
        } catch (e) {
            error.value = 'Nepodařilo se uložit nastavení.'
            throw e
        } finally {
            saving.value = false
        }
    }

    watch(
        () => settings.value.themeMode,
        () => applyTheme()
    )

    return {
        settings,
        loading,
        saving,
        error,
        applyTheme,
        fetchFromApi,
        saveToApi,
    }
}