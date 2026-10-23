import { describe, expect, it } from 'vitest'
import { formatMonthParam, formatMonthTitle, monthGrid, monthRange, parseMonthParam } from '~/utils/dates'
import { dayStatus, monthStats } from '~/utils/dayStatus'

const flat = (grid: (Date | null)[][]) => grid.flat()
const leadingEmpty = (grid: (Date | null)[][]) => grid[0]!.findIndex((d) => d !== null)

describe('monthGrid', () => {
    it('týden začíná pondělím, každý řádek má 7 buněk', () => {
        const grid = monthGrid(new Date(2026, 9, 1)) // 1. 10. 2026 je čtvrtek
        expect(grid.every((week) => week.length === 7)).toBe(true)
        expect(leadingEmpty(grid)).toBe(3) // Po Út St prázdné
        expect(flat(grid).filter(Boolean)).toHaveLength(31)
    })

    it('měsíc začínající v neděli má 6 prázdných buněk a 6 řádků', () => {
        const grid = monthGrid(new Date(2026, 2, 1)) // 1. 3. 2026 je neděle
        expect(leadingEmpty(grid)).toBe(6)
        expect(grid).toHaveLength(6)
    })

    it('přestupný únor má 29 dní', () => {
        const days = flat(monthGrid(new Date(2024, 1, 1))).filter(Boolean) as Date[]
        expect(days).toHaveLength(29)
        expect(days.at(-1)!.getDate()).toBe(29)
    })
})

describe('měsíc v URL a titulek', () => {
    it('převod 2026-10 ↔ datum', () => {
        expect(formatMonthParam(new Date(2026, 9, 23))).toBe('2026-10')
        const parsed = parseMonthParam('2026-10')!
        expect([parsed.getFullYear(), parsed.getMonth(), parsed.getDate()]).toEqual([2026, 9, 1])
    })

    it('neplatný měsíc vrací null', () => {
        expect(parseMonthParam('2026-13')).toBeNull()
        expect(parseMonthParam('2026-1')).toBeNull()
        expect(parseMonthParam(undefined)).toBeNull()
        expect(parseMonthParam(['2026-10'])).toBeNull()
    })

    it('název měsíce česky', () => {
        expect(formatMonthTitle(new Date(2026, 9, 1))).toBe('říjen 2026')
        expect(formatMonthTitle(new Date(2026, 1, 1))).toBe('únor 2026')
    })

    it('rozsah měsíce od prvního do posledního dne', () => {
        const { from, to } = monthRange(new Date(2026, 1, 15))
        expect([from.getDate(), to.getDate()]).toEqual([1, 28])
    })
})

describe('dayStatus / monthStats', () => {
    it('stav dne podle záznamů a bilance', () => {
        expect(dayStatus({ entryCount: 0, balanceKcal: -2100 })).toBe('none')
        expect(dayStatus({ entryCount: 2, balanceKcal: null })).toBe('unknown')
        expect(dayStatus({ entryCount: 2, balanceKcal: -250 })).toBe('deficit')
        expect(dayStatus({ entryCount: 1, balanceKcal: 0 })).toBe('deficit')
        expect(dayStatus({ entryCount: 3, balanceKcal: 400 })).toBe('surplus')
    })

    it('shrnutí měsíce počítá jen dny se záznamem', () => {
        expect(monthStats([
            { entryCount: 0, balanceKcal: -2100 },
            { entryCount: 2, balanceKcal: -250 },
            { entryCount: 1, balanceKcal: 300 },
            { entryCount: 1, balanceKcal: null },
        ])).toEqual({ recordedDays: 3, deficitDays: 1, surplusDays: 1, unknownDays: 1 })
    })
})
