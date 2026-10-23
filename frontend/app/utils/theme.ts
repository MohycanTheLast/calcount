import type { ThemeMode } from '~/api/types'

export const THEME_MODES: ThemeMode[] = ['system', 'light', 'dark']

export const DEFAULT_THEME_MODE: ThemeMode = 'dark'

// Barvy pozadí (--cc-bg) pro <meta name="theme-color"> – lišta prohlížeče/PWA ladí s tématem aplikace
export const THEME_COLORS = { light: '#f8fafc', dark: '#020617' } as const

// Neznámá nebo chybějící hodnota (např. starší „auto“ z backendu) → výchozí tmavé téma
export const normalizeThemeMode = (value: unknown): ThemeMode =>
    THEME_MODES.includes(value as ThemeMode) ? (value as ThemeMode) : DEFAULT_THEME_MODE

export const resolveEffectiveTheme = (mode: ThemeMode, prefersDark: boolean): 'light' | 'dark' =>
    mode === 'system' ? (prefersDark ? 'dark' : 'light') : mode
