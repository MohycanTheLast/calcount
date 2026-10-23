import { describe, expect, it } from 'vitest'
import { activeSourceLabel, parseActiveKcalInput } from '~/utils/activity'

describe('activeSourceLabel', () => {
    it('popisek podle zdroje', () => {
        expect(activeSourceLabel('apple_health')).toBe('Apple Zdraví')
        expect(activeSourceLabel('manual')).toBe('upraveno ručně')
        expect(activeSourceLabel('static')).toBe('průměrná aktivita')
    })

    it('bez aktivních kalorií žádný popisek', () => {
        expect(activeSourceLabel('none')).toBeNull()
    })
})

describe('parseActiveKcalInput', () => {
    it('celé i desetinné číslo (i s čárkou) zaokrouhlí', () => {
        expect(parseActiveKcalInput(450)).toBe(450)
        expect(parseActiveKcalInput('450,6')).toBe(451)
        expect(parseActiveKcalInput('1 200')).toBe(1200)
        expect(parseActiveKcalInput(0)).toBe(0)
    })

    it('prázdné, nečíselné a mimo rozsah odmítne', () => {
        expect(parseActiveKcalInput('')).toBeNull()
        expect(parseActiveKcalInput(null)).toBeNull()
        expect(parseActiveKcalInput('abc')).toBeNull()
        expect(parseActiveKcalInput(-1)).toBeNull()
        expect(parseActiveKcalInput(10001)).toBeNull()
    })
})
