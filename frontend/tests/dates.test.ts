import { describe, expect, it } from 'vitest'
import { addDays, dayLabel, dayTitle, formatIsoDate, parseIsoDate } from '~/utils/dates'

describe('parseIsoDate / formatIsoDate', () => {
    it('převede YYYY-MM-DD na místní datum a zpět', () => {
        const date = parseIsoDate('2026-10-23')
        expect(date).not.toBeNull()
        expect(date!.getFullYear()).toBe(2026)
        expect(date!.getMonth()).toBe(9)
        expect(date!.getDate()).toBe(23)
        expect(formatIsoDate(date!)).toBe('2026-10-23')
    })

    it('doplní nuly u jednociferného dne a měsíce', () => {
        expect(formatIsoDate(new Date(2026, 0, 5))).toBe('2026-01-05')
    })

    it('odmítne neplatný formát i neexistující den', () => {
        expect(parseIsoDate('23.10.2026')).toBeNull()
        expect(parseIsoDate('2026-10-23T10:00')).toBeNull()
        expect(parseIsoDate('2026-02-30')).toBeNull()
        expect(parseIsoDate('')).toBeNull()
    })
})

describe('addDays', () => {
    it('posune datum přes hranici měsíce a roku a původní nezmění', () => {
        const original = new Date(2026, 11, 31)
        expect(formatIsoDate(addDays(original, 1))).toBe('2027-01-01')
        expect(formatIsoDate(addDays(original, -31))).toBe('2026-11-30')
        expect(formatIsoDate(original)).toBe('2026-12-31')
    })
})

describe('dayLabel / dayTitle', () => {
    const now = new Date(2026, 9, 23, 15, 30) // pátek 23. 10. 2026

    it('dnes a včera slovy, jinak den v týdnu s datem', () => {
        expect(dayLabel(new Date(2026, 9, 23), now)).toBe('Dnes')
        expect(dayLabel(new Date(2026, 9, 22), now)).toBe('Včera')
        expect(dayLabel(new Date(2026, 9, 19), now)).toBe('Po 19. 10.')
    })

    it('včera funguje i přes přelom měsíce', () => {
        expect(dayLabel(new Date(2026, 9, 31), new Date(2026, 10, 1))).toBe('Včera')
    })

    it('titulek má vždy den v týdnu a datum', () => {
        expect(dayTitle(new Date(2026, 9, 23))).toBe('Pá 23. 10.')
    })
})
