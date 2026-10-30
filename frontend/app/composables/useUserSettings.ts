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
    activitySource: 'static',
    photoGalleryEnabled: false,
    hydrationEnabled: false,
    waterGoalMl: null,
    caffeineEnabled: false,
    caffeineLimitMg: null,
    macrosEnabled: false,
}

// Vymazané číselné pole (v-model.number) má hodnotu '' – backend čeká číslo nebo null
const emptyToNull = (value: number | null | undefined): number | null =>
    value == null || (value as unknown) === '' ? null : value

const THEME_STORAGE_KEY = 'cc_theme'
const SETTINGS_QUERY_KEY = ['settings'] as const

export const useUserSettings = () => {
    const settings = useState<UserSettings>('user:settings', () => ({ ...DEFAULT_SETTINGS }))
    // true po prvním úspěšném načtení ze serveru (do té doby nepočítáme deficit z výchozích hodnot)
    const loaded = useState<boolean>('user:settings:loaded', () => false)
    const loading = useState<boolean>('user:settings:loading', () => false)
    const saving = useState<boolean>('user:settings:saving', () => false)
    const error = useState<string | null>('user:settings:error', () => null)

    const settingsApi = useSettingsApi()
    const queryClient = useNuxtApp().$queryClient

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
            // Přes cache dotazů: souběžná volání (start aplikace + stránka Nastavení) se sloučí do jednoho
            // a při odhlášení se cache smaže. Formulář a téma dál pracují se stavem `settings`.
            const res = await queryClient.fetchQuery({
                queryKey: SETTINGS_QUERY_KEY,
                queryFn: () => settingsApi.get(),
                staleTime: 0,
            })

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
            const s = settings.value
            const saved: UserSettings = {
                ...s,
                bmrKcal: emptyToNull(s.bmrKcal),
                avgDailyActivityKcal: emptyToNull(s.avgDailyActivityKcal),
                weightKg: s.saveWeight ? emptyToNull(s.weightKg) : null,
                caffeineLimitMg: emptyToNull(s.caffeineLimitMg),
            }
            await settingsApi.save(saved)
            queryClient.setQueryData(SETTINGS_QUERY_KEY, saved)

            applyTheme()
            persistTheme()
        } catch (e: any) {
            // Backend vrací u neplatné hodnoty česky popsanou chybu (např. rozsah BMR)
            error.value = e?.data?.error ?? 'Nepodařilo se uložit nastavení.'
            throw e
        } finally {
            saving.value = false
        }
    }

    return {
        settings,
        loaded,
        loading,
        saving,
        error,
        loadThemeFromStorage,
        applyTheme,
        fetchFromApi,
        saveToApi,
    }
}
