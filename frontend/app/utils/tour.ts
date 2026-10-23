// Výpočty pro úvodního průvodce (features/onboarding) – umístění bubliny vedle zvýrazněného prvku.

export type Box = { top: number; left: number; width: number; height: number }
export type Size = { width: number; height: number }

export type BubblePlacement = {
    top: number
    left: number
    // Strana prvku, na které bublina je – podle ní se kreslí šipka
    side: 'below' | 'above'
    // Vodorovná pozice šipky v bublině (px od levého okraje bubliny)
    arrowLeft: number
}

const clamp = (value: number, min: number, max: number) => Math.min(Math.max(value, min), Math.max(min, max))

/**
 * Bublina pod prvek, a když se tam nevejde, nad něj; když se nevejde nikam (vysoký prvek), na stranu s víc
 * místem a případně přes okraj prvku. Vodorovně na střed prvku. Vždy celá v okně (s okrajem `margin`).
 * Šipka míří na střed prvku.
 */
export const placeBubble = (target: Box, bubble: Size, viewport: Size, gap = 12, margin = 16): BubblePlacement => {
    const belowTop = target.top + target.height + gap
    const aboveTop = target.top - gap - bubble.height
    const fitsBelow = belowTop + bubble.height <= viewport.height - margin
    const fitsAbove = aboveTop >= margin
    const moreRoomBelow = viewport.height - (target.top + target.height) >= target.top

    const side = fitsBelow || (!fitsAbove && moreRoomBelow) ? 'below' : 'above'
    const top = clamp(side === 'below' ? belowTop : aboveTop, margin, viewport.height - margin - bubble.height)

    const targetCenter = target.left + target.width / 2
    const left = clamp(targetCenter - bubble.width / 2, margin, viewport.width - margin - bubble.width)
    const arrowLeft = clamp(targetCenter - left, 20, bubble.width - 20)

    return { top, left, side, arrowLeft }
}

// Obdélník zvýraznění: prvek s odsazením, ořízlý na okno (prvek může být částečně mimo, např. tab bar dole).
// Od okraje okna zůstává `edge` px, aby byl vidět celý rámeček zvýraznění.
export const spotlightBox = (target: Box, viewport: Size, padding = 6, edge = 2): Box => {
    const top = Math.max(target.top - padding, edge)
    const left = Math.max(target.left - padding, edge)
    const bottom = Math.min(target.top + target.height + padding, viewport.height - edge)
    const right = Math.min(target.left + target.width + padding, viewport.width - edge)

    return { top, left, width: Math.max(right - left, 0), height: Math.max(bottom - top, 0) }
}
