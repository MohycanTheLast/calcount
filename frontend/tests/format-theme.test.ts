import { describe, expect, it } from 'vitest'
import { formatKcal, formatLiters } from '~/utils/format'
import { DEFAULT_THEME_MODE, normalizeThemeMode, resolveEffectiveTheme } from '~/utils/theme'

// České formátování odděluje tisíce nezlomitelnou mezerou
const nbsp = (s: string) => s.replace(/ /g, ' ')

describe('formatKcal / formatLiters', () => {
    it('kcal celá čísla s oddělením tisíců', () => {
        expect(formatKcal(1850)).toBe(nbsp('1 850'))
        expect(formatKcal(60)).toBe('60')
        expect(formatKcal(1849.6)).toBe(nbsp('1 850'))
    })

    it('litry s desetinnou čárkou, max. 2 desetinná místa', () => {
        expect(formatLiters(1.5)).toBe('1,5')
        expect(formatLiters(0.333)).toBe('0,33')
        expect(formatLiters(2)).toBe('2')
    })
})

describe('téma', () => {
    it('výchozí je tmavé', () => {
        expect(DEFAULT_THEME_MODE).toBe('dark')
    })

    it('neznámou nebo chybějící hodnotu převede na výchozí', () => {
        expect(normalizeThemeMode('auto')).toBe('dark')
        expect(normalizeThemeMode(null)).toBe('dark')
        expect(normalizeThemeMode('light')).toBe('light')
        expect(normalizeThemeMode('system')).toBe('system')
    })

    it('„system“ se řídí nastavením zařízení', () => {
        expect(resolveEffectiveTheme('system', true)).toBe('dark')
        expect(resolveEffectiveTheme('system', false)).toBe('light')
        expect(resolveEffectiveTheme('light', true)).toBe('light')
    })
})
