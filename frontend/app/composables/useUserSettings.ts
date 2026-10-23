import { useSettingsApi } from '~/api/settings'
import type { UserSettings } from '~/api/types'
import { appStorage } from '~/lib/storage'
import { DEFAULT_THEME_MODE, THEME_COLORS, normalizeThemeMode, resolveEffectiveTheme } from '~/utils/theme'

export type { AiCommentMode, ThemeMode, UserSettings } from '~/api/types'

const DEFAULT_SETTINGS: UserSettings = {
    bmrKcal: null,
    avgDailyActivityKcal: null,
    themeMode: DEFAULT_THEME_MODE,
    aiCommentMode: 'none',
    weightKg: null,
    saveWeight: false,
}

const THEME_STORAGE_KEY = 'cc_theme'

export const useUserSettings = () => {
    const settings = useState<UserSettings>('user:settings', () => ({ ...DEFAULT_SETTINGS }))
    // true po prvním úspěšném načtení ze serveru (do té doby nepočítáme deficit z výchozích hodnot)
    const loaded = useState<boolean>('user:settings:loaded', () => false)
    const loading = useState<boolean>('user:settings:loading', () => false)
    const saving = useState<boolean>('user:settings:saving', () => false)
    const error = useState<string | null>('user:settings:error', () => null)

    const settingsApi = useSettingsApi()

    // Téma se drží i v úložišti zařízení, aby se po načtení stránky aplikovalo hned, ještě před odpovědí API
    const loadThemeFromStorage = () => {
        if (!import.meta.client) return
        settings.value.themeMode = normalizeThemeMode(appStorage.get(THEME_STORAGE_KEY))
    }

    const persistTheme = () => {
        if (!import.meta.client) return
        appStorage.set(THEME_STORAGE_KEY, settings.value.themeMode)
    }

    const applyTheme = () => {
        if (!import.meta.client) return

        const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)')?.matches ?? false
        const effective = resolveEffectiveTheme(settings.value.themeMode, prefersDark)

        document.documentElement.classList.toggle('dark', effective === 'dark')

        document.querySelectorAll('meta[name="theme-color"]').forEach((meta) => {
            meta.removeAttribute('media')
            meta.setAttribute('content', THEME_COLORS[effective])
        })
    }

    const fetchFromApi = async () => {
        loading.value = true
        error.value = null

        try {
            const res = await settingsApi.get()

            settings.value = { ...DEFAULT_SETTINGS, ...res, themeMode: normalizeThemeMode(res.themeMode) }
            loaded.value = true
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
            await settingsApi.save({
                ...settings.value,
                weightKg: settings.value.saveWeight ? settings.value.weightKg : null,
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

    const ensureLoaded = async () => {
        if (loaded.value || loading.value) return
        await fetchFromApi().catch(() => {})
    }

    // Denní výdej z nastavení (BMR + průměrná aktivita); null, dokud není vyplněné BMR
    const dailyExpenditureKcal = computed<number | null>(() => {
        if (!loaded.value || !settings.value.bmrKcal) return null
        return settings.value.bmrKcal + (settings.value.avgDailyActivityKcal ?? 0)
    })

    return {
        settings,
        loaded,
        loading,
        saving,
        error,
        dailyExpenditureKcal,
        ensureLoaded,
        loadThemeFromStorage,
        applyTheme,
        fetchFromApi,
        saveToApi,
    }
}
