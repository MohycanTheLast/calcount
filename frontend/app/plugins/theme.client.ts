// Téma se aplikuje globálně hned při startu aplikace, ne až při otevření stránky nastavení.
export default defineNuxtPlugin(() => {
    const { settings, loadThemeFromStorage, applyTheme } = useUserSettings()

    loadThemeFromStorage()
    applyTheme()

    // Náhled při změně v nastavení
    watch(() => settings.value.themeMode, applyTheme)

    // Reaguj na změny OS tématu, ale jen když je zvolený "system"
    window.matchMedia?.('(prefers-color-scheme: dark)')?.addEventListener?.('change', () => {
        if (settings.value.themeMode === 'system') applyTheme()
    })
})
