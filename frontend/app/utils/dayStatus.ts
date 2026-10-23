import type { DaySummary } from '~/api/types'

// Stav dne pro barvu v kalendáři:
//   none    – bez záznamu,
//   unknown – zapsáno, ale bilance neznámá (chybí BMR),
//   deficit – příjem ≤ výdej (přesně vyrovnaný den se počítá jako deficit 0, stejně jako v Deníku),
//   surplus – příjem > výdej.
export type DayStatus = 'none' | 'unknown' | 'deficit' | 'surplus'

export const dayStatus = (day: Pick<DaySummary, 'entryCount' | 'balanceKcal'>): DayStatus => {
    if (day.entryCount === 0) return 'none'
    if (day.balanceKcal === null) return 'unknown'
    return day.balanceKcal > 0 ? 'surplus' : 'deficit'
}

export type MonthStats = {
    recordedDays: number
    deficitDays: number
    surplusDays: number
    unknownDays: number
}

// Krátké shrnutí měsíce pod kalendářem
export const monthStats = (days: Pick<DaySummary, 'entryCount' | 'balanceKcal'>[]): MonthStats => {
    const stats: MonthStats = { recordedDays: 0, deficitDays: 0, surplusDays: 0, unknownDays: 0 }

    for (const day of days) {
        const status = dayStatus(day)
        if (status === 'none') continue

        stats.recordedDays++
        if (status === 'deficit') stats.deficitDays++
        else if (status === 'surplus') stats.surplusDays++
        else stats.unknownDays++
    }

    return stats
}
