import type { DaySummary } from '~/api/types'

export type ActiveSource = DaySummary['activeSource']

// Stejný rozsah jako na backendu (ActivityController::MAX_ACTIVE_KCAL)
export const MAX_ACTIVE_KCAL = 10000

const SOURCE_LABELS: Record<ActiveSource, string | null> = {
    static: 'průměrná aktivita',
    manual: 'upraveno ručně',
    apple_health: 'Apple Zdraví',
    strava: 'Strava',
    none: null,
}

// Odkud jsou aktivní kalorie dne – popisek pro UI (null = aktivní kalorie nejsou)
export const activeSourceLabel = (source: ActiveSource) => SOURCE_LABELS[source] ?? null

// Aktivní kalorie zadané uživatelem: celé číslo 0–MAX_ACTIVE_KCAL, jinak null
export const parseActiveKcalInput = (value: string | number | null | undefined): number | null => {
    if (value === null || value === undefined || value === '') return null
    const n = typeof value === 'number' ? value : Number(String(value).replace(',', '.').replace(/\s/g, ''))
    if (!Number.isFinite(n)) return null
    const kcal = Math.round(n)
    return kcal >= 0 && kcal <= MAX_ACTIVE_KCAL ? kcal : null
}
