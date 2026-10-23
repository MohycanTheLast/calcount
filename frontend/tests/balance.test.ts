import { describe, expect, it } from 'vitest'
import { dayBalance } from '~/utils/balance'
import { calculateBmr } from '~/utils/bmr'

describe('dayBalance', () => {
    it('deficit, když je příjem nižší než výdej', () => {
        expect(dayBalance(1850, 2100)).toEqual({ isOver: false, differenceKcal: 250, progress: 1850 / 2100 })
    })

    it('přebytek, když je příjem vyšší; ukazatel průběhu je nejvýš 1', () => {
        expect(dayBalance(2500, 2100)).toEqual({ isOver: true, differenceKcal: 400, progress: 1 })
    })

    it('přesně vyrovnaný den se počítá jako deficit 0', () => {
        expect(dayBalance(2100, 2100)).toEqual({ isOver: false, differenceKcal: 0, progress: 1 })
    })

    it('bez známého výdeje (chybí BMR) žádná bilance', () => {
        expect(dayBalance(1850, null)).toBeNull()
        expect(dayBalance(1850, 0)).toBeNull()
    })
})

describe('calculateBmr (Mifflin–St Jeor)', () => {
    it('muž a žena', () => {
        // 10·80 + 6,25·180 − 5·30 + 5 = 1780
        expect(calculateBmr({ sex: 'male', age: 30, heightCm: 180, weightKg: 80 })).toBe(1780)
        // 10·60 + 6,25·165 − 5·40 − 161 = 1270,25 → 1270
        expect(calculateBmr({ sex: 'female', age: 40, heightCm: 165, weightKg: 60 })).toBe(1270)
    })

    it('bez kladných vstupů vrací null', () => {
        expect(calculateBmr({ sex: 'male', age: null, heightCm: 180, weightKg: 80 })).toBeNull()
        expect(calculateBmr({ sex: 'male', age: 30, heightCm: 0, weightKg: 80 })).toBeNull()
        expect(calculateBmr({ sex: 'female', age: 30, heightCm: 170, weightKg: -5 })).toBeNull()
    })
})
