import { describe, expect, it } from 'vitest'
import { formatMacrosShort, goalProgress } from '~/utils/tracking'

describe('goalProgress', () => {
    it('podíl z cíle, nejvýš celý ukazatel', () => {
        expect(goalProgress(500, 2000)).toEqual({ ratio: 0.25, over: false })
        expect(goalProgress(2000, 2000)).toEqual({ ratio: 1, over: false })
        expect(goalProgress(450, 400)).toEqual({ ratio: 1, over: true })
    })

    it('nulový cíl nerozbije výpočet', () => {
        expect(goalProgress(100, 0)).toEqual({ ratio: 0, over: true })
    })
})

describe('formatMacrosShort', () => {
    it('zaokrouhlí na celé gramy', () => {
        expect(formatMacrosShort({ proteinG: 12.4, carbsG: 40, sugarG: 8, fatG: 9.6 })).toBe('B 12 g · S 40 g · C 8 g · T 10 g')
    })

    it('záznam bez odhadu nic nezobrazí', () => {
        expect(formatMacrosShort({ proteinG: null, carbsG: null, sugarG: null, fatG: null })).toBeNull()
    })
})
