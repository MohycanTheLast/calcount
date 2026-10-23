import { describe, expect, it } from 'vitest'
import { placeBubble, spotlightBox } from '~/utils/tour'

// iPhone SE
const viewport = { width: 375, height: 667 }
const bubble = { width: 300, height: 150 }

describe('placeBubble', () => {
    it('pod prvek, vodorovně na střed', () => {
        const p = placeBubble({ top: 100, left: 100, width: 175, height: 50 }, bubble, viewport)
        expect(p.side).toBe('below')
        expect(p.top).toBe(162)
        expect(p.left).toBe(37.5)
        expect(p.arrowLeft).toBe(150)
    })

    it('u spodního okraje (tab bar) nad prvek', () => {
        const p = placeBubble({ top: 610, left: 250, width: 100, height: 50 }, bubble, viewport)
        expect(p.side).toBe('above')
        expect(p.top).toBe(610 - 12 - 150)
    })

    it('nevyjede z okna a šipka míří na prvek', () => {
        const p = placeBubble({ top: 610, left: 300, width: 60, height: 50 }, bubble, viewport)
        expect(p.left).toBe(375 - 16 - 300)
        expect(p.left + p.arrowLeft).toBeCloseTo(330, 0)
    })

    it('šipka zůstane v bublině i u prvku na kraji', () => {
        const p = placeBubble({ top: 100, left: 0, width: 20, height: 20 }, bubble, viewport)
        expect(p.left).toBe(16)
        expect(p.arrowLeft).toBe(20)
    })

    it('vysoký prvek, který se nevejde pod ani nad – bublina celá v okně', () => {
        const p = placeBubble({ top: 60, left: 16, width: 343, height: 500 }, bubble, viewport)
        expect(p.top).toBeGreaterThanOrEqual(16)
        expect(p.top + bubble.height).toBeLessThanOrEqual(667 - 16)
    })
})

describe('spotlightBox', () => {
    it('přidá odsazení', () => {
        expect(spotlightBox({ top: 100, left: 100, width: 50, height: 20 }, viewport)).toEqual({ top: 94, left: 94, width: 62, height: 32 })
    })

    it('ořízne na okno a nechá u okraje místo na rámeček', () => {
        expect(spotlightBox({ top: -20, left: 0, width: 375, height: 50 }, viewport)).toEqual({ top: 2, left: 2, width: 371, height: 34 })
        // záložka v tab baru u spodního okraje
        expect(spotlightBox({ top: 611, left: 130, width: 114, height: 56 }, viewport)).toEqual({ top: 605, left: 124, width: 126, height: 60 })
    })
})
