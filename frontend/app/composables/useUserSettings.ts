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

const THEME_STORAGE_KEY = 'cc_theme'
const THEME_MODES: ThemeMode[] = ['system', 'light', 'dark']

const normalizeThemeMode = (value: unknown): ThemeMode =>
    THEME_MODES.includes(value as ThemeMode) ? value as ThemeMode : 'system'

export const useUserSettings = () => {
    const settings = useState<UserSettings>('user:settings', () => ({ ...DEFAULT_SETTINGS }))
    const loading = useState<boolean>('user:settings:loading', () => false)
    const saving = useState<boolean>('user:settings:saving', () => false)
    const error = useState<string | null>('user:settings:error', () => null)

    const { apiFetch } = useApiFetch()

    // Téma se drží i v localStorage, aby se po načtení stránky aplikovalo hned, ještě před odpovědí API
    const loadThemeFromStorage = () => {
        if (!import.meta.client) return
        try {
            settings.value.themeMode = normalizeThemeMode(localStorage.getItem(THEME_STORAGE_KEY))
        } catch {
            // storage nemusí být dostupné (private mode)
        }
    }

    const persistTheme = () => {
        if (!import.meta.client) return
        try {
            localStorage.setItem(THEME_STORAGE_KEY, settings.value.themeMode)
        } catch {
            // storage nemusí být dostupné (private mode)
        }
    }

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

    const fetchFromApi = async () => {
        loading.value = true
        error.value = null

        try {
            const res = await apiFetch<Partial<UserSettings>>('/api/user/settings', {
                method: 'GET',
            })

            settings.value = { ...DEFAULT_SETTINGS, ...res, themeMode: normalizeThemeMode(res.themeMode) }
            applyTheme()
            persistTheme()
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
        saving.value = true
        error.value = null

        try {
            const payload: UserSettings = {
                ...settings.value,
                saveWeight: settings.value.saveWeight,
                weightKg: settings.value.saveWeight ? settings.value.weightKg : null,
            }

            await apiFetch('/api/user/settings', {
                method: 'POST',
                body: payload,
            })

            applyTheme()
            persistTheme()
        } catch (e) {
            error.value = 'Nepodařilo se uložit nastavení.'
            throw e
        } finally {
            saving.value = false
        }
    }

    return {
        settings,
        loading,
        saving,
        error,
        loadThemeFromStorage,
        applyTheme,
        fetchFromApi,
        saveToApi,
    }
}